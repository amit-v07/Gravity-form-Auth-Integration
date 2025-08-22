<?php
/**
 * Custom Email OTP System
 * Alternative to SMS when OTPless SMS is not available
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class CustomEmailOTP {
    
    public function __construct() {
        add_action('wp_ajax_send_email_otp', array($this, 'send_email_otp'));
        add_action('wp_ajax_nopriv_send_email_otp', array($this, 'send_email_otp'));
        add_action('wp_ajax_verify_email_otp', array($this, 'verify_email_otp'));
        add_action('wp_ajax_nopriv_verify_email_otp', array($this, 'verify_email_otp'));
    }
    
    public function send_email_otp() {
        // Custom nonce verification with better error messages
        if (!wp_verify_nonce($_POST['nonce'], 'email_otp_nonce')) {
            wp_die(json_encode(array(
                'success' => false, 
                'message' => 'Security verification failed. Please refresh the page and try again.'
            )));
        }
        
        $email = sanitize_email($_POST['email']);
        
        if (empty($email)) {
            wp_die(json_encode(array('success' => false, 'message' => 'Email is required')));
        }
        
        // Generate 6-digit OTP
        $otp = sprintf('%06d', mt_rand(0, 999999));
        
        // Store OTP in WordPress options (temporary)
        $otp_key = 'email_otp_' . md5($email . time());
        set_transient($otp_key, $otp, 300); // 5 minutes expiry
        
        // Store email for verification
        set_transient($otp_key . '_email', $email, 300);
        
                 // Send email with better configuration
         $subject = 'Your OTP for Form Submission';
         $message = $this->get_email_template($otp);
         
         // Better email headers for improved delivery
         $headers = array(
             'Content-Type: text/html; charset=UTF-8',
             'From: ' . get_option('blogname') . ' <' . get_option('admin_email') . '>',
             'Reply-To: ' . get_option('admin_email'),
             'X-Mailer: WordPress/' . get_bloginfo('version')
         );
         
         $sent = wp_mail($email, $subject, $message, $headers);
        
        if ($sent) {
            wp_die(json_encode(array(
                'success' => true,
                'otp_key' => $otp_key,
                'message' => 'OTP sent to your email successfully'
            )));
        } else {
            wp_die(json_encode(array(
                'success' => false,
                'message' => 'Failed to send email. Please check your email address.'
            )));
        }
    }
    
    public function verify_email_otp() {
        // Custom nonce verification with better error messages
        if (!wp_verify_nonce($_POST['nonce'], 'email_otp_nonce')) {
            wp_die(json_encode(array(
                'success' => false, 
                'message' => 'Security verification failed. Please refresh the page and try again.'
            )));
        }
        
        $otp = sanitize_text_field($_POST['otp']);
        $otp_key = sanitize_text_field($_POST['otp_key']);
        
        if (empty($otp) || empty($otp_key)) {
            wp_die(json_encode(array('success' => false, 'message' => 'OTP and key are required')));
        }
        
        // Get stored OTP
        $stored_otp = get_transient($otp_key);
        $stored_email = get_transient($otp_key . '_email');
        
        if (!$stored_otp || !$stored_email) {
            wp_die(json_encode(array('success' => false, 'message' => 'OTP expired. Please request a new one.')));
        }
        
        if ($otp === $stored_otp) {
            // OTP verified successfully
            $user_data = array(
                'success' => true,
                'email' => $stored_email,
                'phone_number' => '',
                'name' => '',
                'auth_time' => time()
            );
            
            // Store user details in session
            if (!session_id()) {
                session_start();
            }
            $_SESSION['otpless_user'] = $user_data;
            
            // Clear OTP data
            delete_transient($otp_key);
            delete_transient($otp_key . '_email');
            
            wp_die(json_encode(array(
                'success' => true,
                'user' => $user_data,
                'message' => 'Email OTP verified successfully'
            )));
        } else {
            wp_die(json_encode(array(
                'success' => false,
                'message' => 'Invalid OTP. Please try again.'
            )));
        }
    }
    
    private function get_email_template($otp) {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Your OTP</title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                <div style="background: #f8f9fa; padding: 30px; border-radius: 10px; text-align: center;">
                    <h2 style="color: #0073aa; margin-bottom: 20px;">Your OTP Code</h2>
                    
                    <div style="background: #fff; padding: 20px; border-radius: 8px; border: 2px solid #0073aa; margin: 20px 0;">
                        <h1 style="color: #0073aa; font-size: 32px; letter-spacing: 5px; margin: 0;">' . $otp . '</h1>
                    </div>
                    
                    <p style="margin-bottom: 15px;"><strong>This OTP is valid for 5 minutes.</strong></p>
                    
                    <p style="color: #666; font-size: 14px;">
                        If you didn\'t request this OTP, please ignore this email.
                    </p>
                </div>
                
                <div style="text-align: center; margin-top: 20px; color: #666; font-size: 12px;">
                    <p>This is an automated message. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        ';
    }
}

// Initialize the custom email OTP system
new CustomEmailOTP();
?>
