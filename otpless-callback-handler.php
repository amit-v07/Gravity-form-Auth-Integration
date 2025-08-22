<?php
/**
 * OTPless Callback Handler
 * This file handles the callback from OTPless authentication
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add AJAX action for OTPless callback
add_action('wp_ajax_otpless_callback', 'handle_otpless_callback');
add_action('wp_ajax_nopriv_otpless_callback', 'handle_otpless_callback');

function handle_otpless_callback() {
    // Get the token or code from the URL
    $token = sanitize_text_field($_GET['token'] ?? '');
    $code = sanitize_text_field($_GET['code'] ?? '');
    
    if (empty($token) && empty($code)) {
        wp_die('Invalid callback: No token or code provided');
    }
    
    // Get OTPless credentials
    $client_id = get_option('otpless_client_id', '');
    $client_secret = get_option('otpless_client_secret', '');
    
    if (empty($client_id) || empty($client_secret)) {
        wp_die('OTPless credentials not configured');
    }
    
    try {
        // Load OTPless SDK
        $sdk_path = plugin_dir_path(__FILE__) . 'otpless-php-auth-sdk/vendor/autoload.php';
        if (file_exists($sdk_path)) {
            require_once $sdk_path;
        } else {
            wp_die('OTPless SDK not found');
        }
        
        $auth = new \Otpless\OTPLessAuth();
        
        // Verify the token or code
        if (!empty($token)) {
            $result = $auth->verifyToken($token, $client_id, $client_secret);
        } else {
            $result = $auth->verifyCode($code, $client_id, $client_secret);
        }
        
        $result_array = json_decode($result, true);
        
        if ($result_array['success']) {
            // Store user details in session
            if (!session_id()) {
                session_start();
            }
            $_SESSION['otpless_user'] = $result_array;
            
            // Redirect back to the original page with success message
            $redirect_url = home_url() . '?otpless_auth=success';
            wp_redirect($redirect_url);
            exit;
            
        } else {
            // Redirect with error message
            $redirect_url = home_url() . '?otpless_auth=error&message=' . urlencode($result_array['errorMsg'] ?? 'Authentication failed');
            wp_redirect($redirect_url);
            exit;
        }
        
    } catch (Exception $e) {
        // Redirect with error message
        $redirect_url = home_url() . '?otpless_auth=error&message=' . urlencode('Authentication error: ' . $e->getMessage());
        wp_redirect($redirect_url);
        exit;
    }
}

// Handle authentication status messages
add_action('wp_head', 'handle_otpless_auth_messages');

function handle_otpless_auth_messages() {
    $auth_status = sanitize_text_field($_GET['otpless_auth'] ?? '');
    
    if ($auth_status === 'success') {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Show success message
            $('body').append('<div id="otpless-success" style="position: fixed; top: 20px; right: 20px; background: #4CAF50; color: white; padding: 15px; border-radius: 5px; z-index: 9999; box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);">Authentication successful! You can now submit the form.</div>');
            
            // Remove success message after 5 seconds
            setTimeout(function() {
                $('#otpless-success').fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Clean up URL
            window.history.replaceState({}, document.title, window.location.pathname);
        });
        </script>
        <?php
    } elseif ($auth_status === 'error') {
        $message = sanitize_text_field($_GET['message'] ?? 'Authentication failed');
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Show error message
            $('body').append('<div id="otpless-error" style="position: fixed; top: 20px; right: 20px; background: #f44336; color: white; padding: 15px; border-radius: 5px; z-index: 9999; box-shadow: 0 4px 12px rgba(244, 67, 54, 0.3);"><?php echo esc_js($message); ?></div>');
            
            // Remove error message after 5 seconds
            setTimeout(function() {
                $('#otpless-error').fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Clean up URL
            window.history.replaceState({}, document.title, window.location.pathname);
        });
        </script>
        <?php
    }
}
