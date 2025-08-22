<?php
/**
 * OTPless Gravity Forms Integration - Installation Script
 * 
 * This script helps set up the plugin and verify the installation.
 * Run this script once after uploading the plugin files.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // If not in WordPress context, check if we can load WordPress
    $wp_load_path = dirname(__FILE__) . '/../../../wp-load.php';
    if (file_exists($wp_load_path)) {
        require_once $wp_load_path;
    } else {
        die('WordPress not found. Please run this script from within WordPress.');
    }
}

// Check if we're in WordPress admin
if (!is_admin()) {
    die('This script must be run from WordPress admin area.');
}

// Installation class
class OTPlessGFInstallation {
    
    private $errors = array();
    private $warnings = array();
    private $success = array();
    
    public function run() {
        echo '<div style="max-width: 800px; margin: 20px auto; padding: 20px; background: #fff; border: 1px solid #ddd; border-radius: 8px;">';
        echo '<h1 style="color: #0073aa;">OTPless Gravity Forms Integration - Installation Check</h1>';
        
        $this->checkRequirements();
        $this->checkFiles();
        $this->checkDependencies();
        $this->checkPermissions();
        $this->checkConfiguration();
        
        $this->displayResults();
        
        echo '</div>';
    }
    
    private function checkRequirements() {
        echo '<h2>Checking Requirements...</h2>';
        
        // Check WordPress version
        global $wp_version;
        if (version_compare($wp_version, '5.0', '>=')) {
            $this->success[] = "WordPress version: $wp_version ✓";
        } else {
            $this->errors[] = "WordPress version $wp_version is too old. Requires 5.0 or higher.";
        }
        
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '>=')) {
            $this->success[] = "PHP version: " . PHP_VERSION . " ✓";
        } else {
            $this->errors[] = "PHP version " . PHP_VERSION . " is too old. Requires 7.4 or higher.";
        }
        
        // Check if Gravity Forms is active
        if (class_exists('GFCommon')) {
            $this->success[] = "Gravity Forms is active ✓";
        } else {
            $this->errors[] = "Gravity Forms plugin is not active.";
        }
        
        // Check if cURL is available
        if (function_exists('curl_init')) {
            $this->success[] = "cURL extension is available ✓";
        } else {
            $this->errors[] = "cURL extension is not available.";
        }
        
        // Check if JSON extension is available
        if (function_exists('json_encode')) {
            $this->success[] = "JSON extension is available ✓";
        } else {
            $this->errors[] = "JSON extension is not available.";
        }
    }
    
    private function checkFiles() {
        echo '<h2>Checking Files...</h2>';
        
        $plugin_dir = plugin_dir_path(__FILE__);
        
        $required_files = array(
            'otpless-gravity-forms-integration.php' => 'Main plugin file',
            'otpless-callback-handler.php' => 'Callback handler',
            'assets/js/otpless-gravity-forms.js' => 'JavaScript file',
            'assets/css/otpless-gravity-forms.css' => 'CSS file',
            'otpless-php-auth-sdk/vendor/autoload.php' => 'OTPless SDK autoloader',
            'otpless-php-auth-sdk/src/OTPLessAuth.php' => 'OTPless SDK main class'
        );
        
        foreach ($required_files as $file => $description) {
            $file_path = $plugin_dir . $file;
            if (file_exists($file_path)) {
                $this->success[] = "$description exists ✓";
            } else {
                $this->errors[] = "$description is missing: $file";
            }
        }
    }
    
    private function checkDependencies() {
        echo '<h2>Checking Dependencies...</h2>';
        
        $sdk_path = plugin_dir_path(__FILE__) . 'otpless-php-auth-sdk/vendor/autoload.php';
        
        if (file_exists($sdk_path)) {
            try {
                require_once $sdk_path;
                
                // Check if OTPlessAuth class exists
                if (class_exists('\Otpless\OTPLessAuth')) {
                    $this->success[] = "OTPless SDK classes loaded successfully ✓";
                } else {
                    $this->errors[] = "OTPless SDK classes could not be loaded.";
                }
                
                // Check for required dependencies
                $required_classes = array(
                    'GuzzleHttp\Client' => 'Guzzle HTTP Client',
                    'Firebase\JWT\JWT' => 'Firebase JWT',
                    'phpseclib3\Crypt\PublicKeyLoader' => 'phpseclib'
                );
                
                foreach ($required_classes as $class => $description) {
                    if (class_exists($class)) {
                        $this->success[] = "$description is available ✓";
                    } else {
                        $this->warnings[] = "$description is not available.";
                    }
                }
                
            } catch (Exception $e) {
                $this->errors[] = "Error loading OTPless SDK: " . $e->getMessage();
            }
        } else {
            $this->errors[] = "OTPless SDK autoloader not found.";
        }
    }
    
    private function checkPermissions() {
        echo '<h2>Checking Permissions...</h2>';
        
        $plugin_dir = plugin_dir_path(__FILE__);
        
        // Check if plugin directory is readable
        if (is_readable($plugin_dir)) {
            $this->success[] = "Plugin directory is readable ✓";
        } else {
            $this->errors[] = "Plugin directory is not readable.";
        }
        
        // Check if assets directory is readable
        $assets_dir = $plugin_dir . 'assets/';
        if (is_readable($assets_dir)) {
            $this->success[] = "Assets directory is readable ✓";
        } else {
            $this->errors[] = "Assets directory is not readable.";
        }
        
        // Check if SDK directory is readable
        $sdk_dir = $plugin_dir . 'otpless-php-auth-sdk/';
        if (is_readable($sdk_dir)) {
            $this->success[] = "SDK directory is readable ✓";
        } else {
            $this->errors[] = "SDK directory is not readable.";
        }
    }
    
    private function checkConfiguration() {
        echo '<h2>Checking Configuration...</h2>';
        
        // Check if plugin is active
        if (is_plugin_active('otpless-gravity-forms-integration/otpless-gravity-forms-integration.php')) {
            $this->success[] = "Plugin is active ✓";
        } else {
            $this->warnings[] = "Plugin is not active. Please activate it from the Plugins page.";
        }
        
        // Check OTPless credentials
        $client_id = get_option('otpless_client_id', '');
        $client_secret = get_option('otpless_client_secret', '');
        
        if (!empty($client_id) && !empty($client_secret)) {
            $this->success[] = "OTPless credentials are configured ✓";
        } else {
            $this->warnings[] = "OTPless credentials are not configured. Please go to Settings → OTPless to configure them.";
        }
        
        // Check if AJAX actions are registered
        global $wp_filter;
        if (isset($wp_filter['wp_ajax_otpless_verify_auth'])) {
            $this->success[] = "AJAX actions are registered ✓";
        } else {
            $this->warnings[] = "AJAX actions are not registered. Plugin may not be properly loaded.";
        }
    }
    
    private function displayResults() {
        echo '<h2>Installation Results</h2>';
        
        if (!empty($this->success)) {
            echo '<div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 15px;">';
            echo '<h3 style="margin-top: 0; color: #155724;">✓ Success</h3>';
            echo '<ul style="margin: 0; padding-left: 20px;">';
            foreach ($this->success as $message) {
                echo '<li>' . esc_html($message) . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }
        
        if (!empty($this->warnings)) {
            echo '<div style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin-bottom: 15px;">';
            echo '<h3 style="margin-top: 0; color: #856404;">⚠ Warnings</h3>';
            echo '<ul style="margin: 0; padding-left: 20px;">';
            foreach ($this->warnings as $message) {
                echo '<li>' . esc_html($message) . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }
        
        if (!empty($this->errors)) {
            echo '<div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 15px;">';
            echo '<h3 style="margin-top: 0; color: #721c24;">✗ Errors</h3>';
            echo '<ul style="margin: 0; padding-left: 20px;">';
            foreach ($this->errors as $message) {
                echo '<li>' . esc_html($message) . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }
        
        // Overall status
        if (empty($this->errors)) {
            echo '<div style="background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin-bottom: 15px;">';
            echo '<h3 style="margin-top: 0; color: #0c5460;">🎉 Installation Complete!</h3>';
            echo '<p>Your OTPless Gravity Forms integration is ready to use. If you see any warnings above, please address them for optimal functionality.</p>';
            echo '</div>';
        } else {
            echo '<div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 15px;">';
            echo '<h3 style="margin-top: 0; color: #721c24;">❌ Installation Issues</h3>';
            echo '<p>Please fix the errors above before using the plugin.</p>';
            echo '</div>';
        }
        
        // Next steps
        echo '<div style="background: #e2e3e5; color: #383d41; padding: 15px; border-radius: 5px;">';
        echo '<h3 style="margin-top: 0; color: #383d41;">Next Steps</h3>';
        echo '<ol style="margin: 0; padding-left: 20px;">';
        echo '<li>Configure your OTPless credentials in <strong>Settings → OTPless</strong></li>';
        echo '<li>Test the integration with a Gravity Form</li>';
        echo '<li>Check the browser console for any JavaScript errors</li>';
        echo '<li>Review the plugin documentation for advanced configuration</li>';
        echo '</ol>';
        echo '</div>';
    }
}

// Run the installation check
if (isset($_GET['run_install_check'])) {
    $installation = new OTPlessGFInstallation();
    $installation->run();
} else {
    echo '<div style="max-width: 800px; margin: 20px auto; padding: 20px; background: #fff; border: 1px solid #ddd; border-radius: 8px;">';
    echo '<h1 style="color: #0073aa;">OTPless Gravity Forms Integration - Installation Check</h1>';
    echo '<p>This script will check your installation and verify that all requirements are met.</p>';
    echo '<p><a href="?run_install_check=1" style="background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Run Installation Check</a></p>';
    echo '</div>';
}
?>
