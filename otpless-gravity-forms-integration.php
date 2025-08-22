<?php
/**
 * Plugin Name: OTPless Gravity Forms Integration
 * Description: Integrate OTPless authentication with Gravity Forms for secure form submissions
 * Version: 1.0.0
 * Author: Amit
 * License: GPL v2 or later
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('OTPLESS_GF_VERSION', '1.0.0');
define('OTPLESS_GF_PLUGIN_URL', plugin_dir_url(__FILE__));
define('OTPLESS_GF_PLUGIN_PATH', plugin_dir_path(__FILE__));

class OTPlessGravityFormsIntegration {
    
    private $client_id;
    private $client_secret;
    public $sdk_loaded;
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_otpless_verify_auth', array($this, 'verify_otpless_auth'));
        add_action('wp_ajax_nopriv_otpless_verify_auth', array($this, 'verify_otpless_auth'));
                add_action('wp_ajax_otpless_send_otp', array($this, 'send_otp'));
        add_action('wp_ajax_nopriv_otpless_send_otp', array($this, 'send_otp'));
        add_action('wp_ajax_otpless_verify_otp', array($this, 'verify_otp'));
        add_action('wp_ajax_nopriv_otpless_verify_otp', array($this, 'verify_otp'));
        add_action('wp_ajax_otpless_verify_code', array($this, 'verify_code'));
        add_action('wp_ajax_nopriv_otpless_verify_code', array($this, 'verify_code'));
         
         // Include custom email OTP system
         require_once OTPLESS_GF_PLUGIN_PATH . 'custom-email-otp.php';
        add_action('gform_after_submission', array($this, 'after_form_submission'), 10, 2);
        add_filter('gform_form_args', array($this, 'add_otpless_to_form'));
        // Removed static modal - now created dynamically with JavaScript
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Load OTPless SDK
        $this->sdk_loaded = $this->load_otpless_sdk();
        
        // Get credentials from WordPress options
        $this->client_id = get_option('otpless_client_id', '');
        $this->client_secret = get_option('otpless_client_secret', '');
    }
    
    public function init() {
        // Initialize plugin
    }
    
    private function load_otpless_sdk() {
        // Load the OTPless SDK
        $sdk_path = OTPLESS_GF_PLUGIN_PATH . 'otpless-php-auth-sdk/vendor/autoload.php';
        
        if (!file_exists($sdk_path)) {
            error_log('OTPless SDK not found at: ' . $sdk_path);
            return false;
        }
        
        try {
            require_once $sdk_path;
            
            // Check if the class exists
            if (!class_exists('\Otpless\OTPLessAuth')) {
                error_log('OTPlessAuth class not found after loading SDK');
                return false;
            }
            
            return true;
        } catch (Exception $e) {
            error_log('Error loading OTPless SDK: ' . $e->getMessage());
            return false;
        }
    }
    
    public function enqueue_scripts() {
        // Enqueue OTPless SDK JavaScript
        wp_enqueue_script(
            'otpless-sdk',
            'https://otpless.com/auth.js',
            array(),
            OTPLESS_GF_VERSION,
            true
        );
        
        // Enqueue our custom JavaScript
        wp_enqueue_script(
            'otpless-gravity-forms',
            OTPLESS_GF_PLUGIN_URL . 'assets/js/otpless-gravity-forms.js',
            array('jquery', 'otpless-sdk'),
            OTPLESS_GF_VERSION,
            true
        );
        
                 // Localize script with AJAX URL and credentials
         wp_localize_script('otpless-gravity-forms', 'otpless_ajax', array(
             'ajax_url' => admin_url('admin-ajax.php'),
             'nonce' => wp_create_nonce('otpless_nonce'),
             'email_otp_nonce' => wp_create_nonce('email_otp_nonce'),
             'client_id' => $this->client_id,
             'redirect_uri' => admin_url('admin-ajax.php?action=otpless_callback'),
             'enabled_forms' => get_option('otpless_enabled_forms', array())
         ));
        
        // Enqueue CSS
        wp_enqueue_style(
            'otpless-gravity-forms',
            OTPLESS_GF_PLUGIN_URL . 'assets/css/otpless-gravity-forms.css',
            array(),
            OTPLESS_GF_VERSION
        );
    }
    
    public function add_otpless_to_form($args) {
        // Add OTPless authentication to all Gravity Forms
        if (!empty($args['form'])) {
            $args['form']['otpless_enabled'] = true;
        }
        return $args;
    }
    
    public function send_otp() {
        // Custom nonce verification with better error messages
        if (!wp_verify_nonce($_POST['nonce'], 'otpless_nonce')) {
            wp_die(json_encode(array(
                'success' => false, 
                'message' => 'Security verification failed. Please refresh the page and try again.'
            )));
        }
        
        // Check if SDK is loaded
        if (!$this->sdk_loaded) {
            error_log('OTPless SDK not loaded');
            wp_die(json_encode(array('success' => false, 'message' => 'OTPless SDK not available. Please check plugin installation.')));
        }
        
        // Check if credentials are configured
        if (empty($this->client_id) || empty($this->client_secret)) {
            error_log('OTPless credentials not configured');
            wp_die(json_encode(array('success' => false, 'message' => 'OTPless credentials not configured. Please configure in admin settings.')));
        }
        
        $email = sanitize_email($_POST['email']);
        $phone = sanitize_text_field($_POST['phone']);
        $channel = sanitize_text_field($_POST['channel']);
        
        if (empty($email) && empty($phone)) {
            wp_die(json_encode(array('success' => false, 'message' => 'Email or phone number is required')));
        }
        
        try {
            // Check if class exists
            if (!class_exists('\Otpless\OTPLessAuth')) {
                error_log('OTPlessAuth class not found');
                wp_die(json_encode(array('success' => false, 'message' => 'OTPless SDK class not found. Please reinstall the plugin.')));
            }
            
            $auth = new \Otpless\OTPLessAuth();
            
            // Generate a unique order ID
            $order_id = 'GF_' . time() . '_' . rand(1000, 9999);
            
            // Set OTP expiry to 5 minutes
            $expiry = 300;
            
            // Use 6-digit OTP
            $otp_length = '6';
            
            // Generate hash for mobile app (if needed)
            $hash = '';
            
            $result = $auth->sendOtp(
                $phone,
                $email,
                $order_id,
                $expiry,
                $hash,
                $this->client_id,
                $this->client_secret,
                $otp_length,
                $channel
            );
            
            $result_array = json_decode($result, true);
            
            if ($result_array['success']) {
                // Store order ID in session for verification
                if (!session_id()) {
                    session_start();
                }
                $_SESSION['otpless_order_id'] = $result_array['orderId'];
                $_SESSION['otpless_phone'] = $phone;
                $_SESSION['otpless_email'] = $email;
                
                wp_die(json_encode(array(
                     'success' => true,
                     'order_id' => $result_array['orderId'],
                     'message' => 'OTP sent successfully to your ' . ($channel === 'WHATSAPP' ? 'WhatsApp' : 'SMS')
                 )));
            } else {
                wp_die(json_encode(array(
                    'success' => false,
                    'message' => $result_array['message'] ?? 'Failed to send OTP'
                )));
            }
            
        } catch (Exception $e) {
            wp_die(json_encode(array(
                'success' => false,
                'message' => 'Error sending OTP: ' . $e->getMessage()
            )));
        }
    }
    
    public function verify_otp() {
        // Custom nonce verification with better error messages
        if (!wp_verify_nonce($_POST['nonce'], 'otpless_nonce')) {
            wp_die(json_encode(array(
                'success' => false, 
                'message' => 'Security verification failed. Please refresh the page and try again.'
            )));
        }
        
        // Check if SDK is loaded
        if (!$this->sdk_loaded) {
            error_log('OTPless SDK not loaded');
            wp_die(json_encode(array('success' => false, 'message' => 'OTPless SDK not available. Please check plugin installation.')));
        }
        
        // Check if credentials are configured
        if (empty($this->client_id) || empty($this->client_secret)) {
            error_log('OTPless credentials not configured');
            wp_die(json_encode(array('success' => false, 'message' => 'OTPless credentials not configured. Please configure in admin settings.')));
        }
        
        $otp = sanitize_text_field($_POST['otp']);
        
        if (empty($otp)) {
            wp_die(json_encode(array('success' => false, 'message' => 'OTP is required')));
        }
        
        // Get stored session data
        if (!session_id()) {
            session_start();
        }
        
        $order_id = $_SESSION['otpless_order_id'] ?? '';
        $phone = $_SESSION['otpless_phone'] ?? '';
        $email = $_SESSION['otpless_email'] ?? '';
        
        if (empty($order_id)) {
            wp_die(json_encode(array('success' => false, 'message' => 'No OTP request found. Please request OTP again.')));
        }
        
        try {
            // Check if class exists
            if (!class_exists('\Otpless\OTPLessAuth')) {
                error_log('OTPlessAuth class not found');
                wp_die(json_encode(array('success' => false, 'message' => 'OTPless SDK class not found. Please reinstall the plugin.')));
            }
            
            $auth = new \Otpless\OTPLessAuth();
            
            $result = $auth->verifyOtp(
                $phone,
                $email,
                $order_id,
                $otp,
                $this->client_id,
                $this->client_secret
            );
            
            $result_array = json_decode($result, true);
            
            if ($result_array['success'] && $result_array['isOTPVerified']) {
                // OTP verified successfully
                // Create user data from phone/email
                $user_data = array(
                    'success' => true,
                    'phone_number' => $phone,
                    'email' => $email,
                    'name' => '', // Will be filled if available
                    'auth_time' => time()
                );
                
                // Store user details in session
                $_SESSION['otpless_user'] = $user_data;
                
                // Clear OTP session data
                unset($_SESSION['otpless_order_id']);
                unset($_SESSION['otpless_phone']);
                unset($_SESSION['otpless_email']);
                
                wp_die(json_encode(array(
                    'success' => true,
                    'user' => $user_data,
                    'message' => 'OTP verified successfully'
                )));
            } else {
                wp_die(json_encode(array(
                    'success' => false,
                    'message' => 'Invalid OTP. Please try again.'
                )));
            }
            
        } catch (Exception $e) {
            wp_die(json_encode(array(
                'success' => false,
                'message' => 'Error verifying OTP: ' . $e->getMessage()
            )));
        }
    }
    
    public function verify_otpless_auth() {
        check_ajax_referer('otpless_nonce', 'nonce');
        
        $token = sanitize_text_field($_POST['token']);
        $code = sanitize_text_field($_POST['code']);
        
        if (empty($token) && empty($code)) {
            wp_die(json_encode(array('success' => false, 'message' => 'Token or code is required')));
        }
        
        try {
            $auth = new \Otpless\OTPLessAuth();
            
            if (!empty($token)) {
                $result = $auth->verifyToken($token, $this->client_id, $this->client_secret);
            } else {
                $result = $auth->verifyCode($code, $this->client_id, $this->client_secret);
            }
            
            $result_array = json_decode($result, true);
            
            if ($result_array['success']) {
                // Store user details in session
                if (!session_id()) {
                    session_start();
                }
                $_SESSION['otpless_user'] = $result_array;
                
                wp_die(json_encode(array(
                    'success' => true,
                    'user' => $result_array,
                    'message' => 'Authentication successful'
                )));
            } else {
                wp_die(json_encode(array(
                    'success' => false,
                    'message' => $result_array['errorMsg'] ?? 'Authentication failed'
                )));
            }
            
        } catch (Exception $e) {
            wp_die(json_encode(array(
                'success' => false,
                'message' => 'Error verifying authentication: ' . $e->getMessage()
            )));
        }
    }
    
    public function after_form_submission($entry, $form) {
        // Check if user is authenticated with OTPless
        if (!session_id()) {
            session_start();
        }
        
        if (isset($_SESSION['otpless_user'])) {
            // Add OTPless user data to form entry
            $user_data = $_SESSION['otpless_user'];
            
            // You can map OTPless user data to form fields here
            // For example, if you have fields for email, phone, name
            $email_field_id = $this->get_field_id_by_label($form, 'Email');
            $phone_field_id = $this->get_field_id_by_label($form, 'Phone');
            $name_field_id = $this->get_field_id_by_label($form, 'Name');
            
            if ($email_field_id && !empty($user_data['email'])) {
                gform_update_meta($entry['id'], $email_field_id, $user_data['email']);
            }
            
            if ($phone_field_id && !empty($user_data['phone_number'])) {
                gform_update_meta($entry['id'], $phone_field_id, $user_data['phone_number']);
            }
            
            if ($name_field_id && !empty($user_data['name'])) {
                gform_update_meta($entry['id'], $name_field_id, $user_data['name']);
            }
            
            // Clear session after successful submission
            unset($_SESSION['otpless_user']);
        }
    }
    
    private function get_field_id_by_label($form, $label) {
        foreach ($form['fields'] as $field) {
            if (strtolower($field['label']) === strtolower($label)) {
                return $field['id'];
            }
        }
        return false;
    }
    
    // Modal is now created dynamically with JavaScript for better control and styling
    
    // Admin settings
    public function add_admin_menu() {
        add_options_page(
            'OTPless Settings',
            'OTPless',
            'manage_options',
            'otpless-settings',
            array($this, 'admin_page')
        );
    }
    
    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('otpless_client_id', sanitize_text_field($_POST['client_id']));
            update_option('otpless_client_secret', sanitize_text_field($_POST['client_secret']));
            update_option('otpless_enabled_forms', isset($_POST['enabled_forms']) ? array_map('intval', $_POST['enabled_forms']) : array());
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        
        $client_id = get_option('otpless_client_id', '');
        $client_secret = get_option('otpless_client_secret', '');
        $enabled_forms = get_option('otpless_enabled_forms', array());
        
        // Get all Gravity Forms
        $all_forms = array();
        if (class_exists('GFAPI')) {
            $forms = GFAPI::get_forms();
            foreach ($forms as $form) {
                $all_forms[$form['id']] = $form['title'];
            }
        }
        ?>
        <div class="wrap">
            <h1>OTPless Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th scope="row">Client ID</th>
                        <td><input type="text" name="client_id" value="<?php echo esc_attr($client_id); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Client Secret</th>
                        <td><input type="password" name="client_secret" value="<?php echo esc_attr($client_secret); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Enable OTPless for Forms</th>
                        <td>
                            <?php if (empty($all_forms)): ?>
                                <p style="color: #666;">No Gravity Forms found. Please create forms first.</p>
                            <?php else: ?>
                                <select name="enabled_forms[]" multiple="multiple" style="width: 100%; min-height: 150px;">
                                    <?php foreach ($all_forms as $form_id => $form_title): ?>
                                        <option value="<?php echo esc_attr($form_id); ?>" <?php echo in_array($form_id, $enabled_forms) ? 'selected' : ''; ?>>
                                            <?php echo esc_html($form_title); ?> (ID: <?php echo esc_html($form_id); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description">Hold Ctrl/Cmd to select multiple forms. Leave empty to disable OTPless for all forms.</p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    public function verify_code() {
        // Custom nonce verification 
        if (!wp_verify_nonce($_POST['nonce'], 'otpless_nonce')) {
            wp_die(json_encode(array(
                'success' => false, 
                'message' => 'Security verification failed. Please refresh the page and try again.'
            )));
        }
        
        // Check if SDK is loaded
        if (!$this->sdk_loaded) {
            error_log('OTPless SDK not loaded');
            wp_die(json_encode(array('success' => false, 'message' => 'OTPless SDK not available. Please check plugin installation.')));
        }
        
        // Check if credentials are configured
        if (empty($this->client_id) || empty($this->client_secret)) {
            wp_die(json_encode(array('success' => false, 'message' => 'OTPless credentials not configured. Please check plugin settings.')));
        }
        
        $code = sanitize_text_field($_POST['code']);
        
        if (empty($code)) {
            wp_die(json_encode(array('success' => false, 'message' => 'Authorization code is required')));
        }
        
        try {
            // Use OTPless SDK to verify OAuth code
            $result = $this->otpless_auth->verifyCode($code, $this->client_id, $this->client_secret);
            $result_array = json_decode($result, true);
            
            if ($result_array && $result_array['success']) {
                // Store user details in session
                if (!session_id()) {
                    session_start();
                }
                
                $user_data = array(
                    'success' => true,
                    'email' => $result_array['email'] ?? '',
                    'phone_number' => $result_array['phone_number'] ?? '',
                    'name' => $result_array['name'] ?? '',
                    'auth_time' => $result_array['auth_time'] ?? time(),
                    'provider' => 'google'
                );
                
                $_SESSION['otpless_user'] = $user_data;
                
                wp_die(json_encode(array(
                    'success' => true,
                    'user' => $user_data,
                    'message' => 'Google Sign-In successful'
                )));
            } else {
                wp_die(json_encode(array(
                    'success' => false,
                    'message' => $result_array['errorMsg'] ?? 'Google Sign-In failed'
                )));
            }
            
        } catch (Exception $e) {
            error_log('OTPless verify code error: ' . $e->getMessage());
            wp_die(json_encode(array(
                'success' => false,
                'message' => 'Google Sign-In failed. Please try again.'
            )));
        }
    }
}

// Initialize the plugin
new OTPlessGravityFormsIntegration();
