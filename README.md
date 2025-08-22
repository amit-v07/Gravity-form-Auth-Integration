# OTPless Gravity Forms Integration

A WordPress plugin that integrates OTPless authentication with Gravity Forms, providing secure form submissions with phone/email verification.

## Features

- **OTP Authentication**: Users can authenticate via email, WhatsApp, or SMS using one-time passwords
- **Seamless Integration**: Works with any Gravity Form without modifying existing forms
- **User Data Mapping**: Automatically maps authenticated user data to form fields
- **Session Management**: Maintains authentication state across form submissions
- **Responsive Design**: Mobile-friendly authentication modal
- **Admin Settings**: Easy configuration through WordPress admin panel

## Requirements

- WordPress 5.0 or higher
- Gravity Forms plugin
- PHP 7.4 or higher
- OTPless account and credentials

## Installation

### 1. Upload Plugin Files

1. Upload the plugin files to your WordPress site's `/wp-content/plugins/otpless-gravity-forms-integration/` directory
2. Ensure the OTPless PHP SDK is included in the `otpless-php-auth-sdk/` directory

### 2. Activate the Plugin

1. Go to **WordPress Admin → Plugins**
2. Find "OTPless Gravity Forms Integration" and click **Activate**

### 3. Configure OTPless Credentials

1. Go to **WordPress Admin → Settings → OTPless**
2. Enter your OTPless Client ID and Client Secret
3. Click **Save Changes**

### 4. Get OTPless Credentials

1. Sign up at [OTPless.com](https://otpless.com)
2. Create a new application
3. Get your Client ID and Client Secret from the dashboard
4. Configure your redirect URI as: `https://yourdomain.com/wp-admin/admin-ajax.php?action=otpless_callback`

## How It Works

### User Flow

1. **User fills out a Gravity Form**
2. **User clicks submit button**
3. **OTPless authentication modal appears**
4. **User enters email or phone number**
5. **User selects OTP delivery method (WhatsApp/SMS/Email)**
6. **OTP is sent to user's device**
7. **User enters the 6-digit OTP**
8. **OTP is verified and user is authenticated**
9. **Form is automatically submitted with user data**

### Technical Flow

1. **Form Interception**: JavaScript intercepts form submissions
2. **Authentication Check**: Checks if user is already authenticated
3. **OTP Generation**: Sends OTP request to OTPless
4. **User Authentication**: User enters OTP in modal
5. **OTP Verification**: Backend verifies OTP with OTPless
6. **Form Submission**: Form is submitted with authenticated user data

## Configuration

### Admin Settings

The plugin provides an admin settings page at **Settings → OTPless** with the following options:

- **Client ID**: Your OTPless application Client ID
- **Client Secret**: Your OTPless application Client Secret

### Form Field Mapping

The plugin automatically maps authenticated user data to form fields based on field labels:

- **Email**: Maps to fields with label "Email"
- **Phone**: Maps to fields with label "Phone"
- **Name**: Maps to fields with label "Name"

### Custom Field Mapping

To customize field mapping, modify the `get_field_id_by_label()` method in the main plugin file:

```php
private function get_field_id_by_label($form, $label) {
    foreach ($form['fields'] as $field) {
        // Add your custom mapping logic here
        if (strtolower($field['label']) === strtolower($label)) {
            return $field['id'];
        }
    }
    return false;
}
```

## Usage Examples

### Basic Implementation

The plugin works automatically with all Gravity Forms. No additional configuration is required.

### Selective Form Integration

To enable OTPless authentication only for specific forms, add this code to your theme's `functions.php`:

```php
// Enable OTPless for specific form IDs
add_filter('gform_form_args', function($args) {
    $otpless_forms = array(1, 2, 3); // Add your form IDs here
    
    if (in_array($args['form']['id'], $otpless_forms)) {
        $args['form']['otpless_enabled'] = true;
    }
    
    return $args;
});
```

### Custom Authentication Requirements

To add custom authentication requirements, modify the JavaScript file:

```javascript
// Add custom validation
$form.on('submit', function(e) {
    if (customValidationRequired() && !isUserAuthenticated()) {
        e.preventDefault();
        showOTPlessModal();
        return false;
    }
});
```

## File Structure

```
otpless-gravity-forms-integration/
├── otpless-gravity-forms-integration.php    # Main plugin file
├── otpless-callback-handler.php             # Callback handler
├── assets/
│   ├── js/
│   │   └── otpless-gravity-forms.js        # Frontend JavaScript
│   └── css/
│       └── otpless-gravity-forms.css       # Styles
├── otpless-php-auth-sdk/                   # OTPless PHP SDK
└── README.md                               # This file
```

## API Reference

### JavaScript Functions

#### `showOTPlessModal()`
Shows the OTPless authentication modal.

#### `hideOTPlessModal()`
Hides the OTPless authentication modal.

#### `isUserAuthenticated()`
Returns `true` if user is authenticated, `false` otherwise.

#### `setUserAuthenticated(userData)`
Sets user as authenticated with provided user data.

#### `clearAuthentication()`
Clears user authentication data.

### PHP Methods

#### `send_otp()`
Sends OTP to user's email/phone/WhatsApp.

#### `verify_otp()`
Verifies OTP entered by user.

#### `after_form_submission($entry, $form)`
Handles post-submission processing with authenticated user data.

## Troubleshooting

### Common Issues

#### 1. "OTPless SDK not found" Error
- Ensure the OTPless PHP SDK is properly uploaded to the `otpless-php-auth-sdk/` directory
- Check file permissions on the SDK directory

#### 2. "Invalid callback" Error
- Verify your redirect URI is correctly configured in OTPless dashboard
- Ensure the callback URL matches: `https://yourdomain.com/wp-admin/admin-ajax.php?action=otpless_callback`

#### 3. OTP Not Received
- Check spam/junk folders
- Verify email/phone number format
- Ensure OTPless credentials are correct
- Check if the selected delivery method is supported

#### 4. Form Not Submitting After Authentication
- Check browser console for JavaScript errors
- Verify Gravity Forms is properly configured
- Check if form validation is preventing submission

#### 5. User Data Not Mapping to Form Fields
- Ensure form fields have correct labels ("Email", "Phone", "Name")
- Check field IDs in Gravity Forms admin
- Verify user data is being received from OTPless

### Debug Mode

Enable debug mode by adding this to your `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Check the debug log at `/wp-content/debug.log` for detailed error messages.

### Browser Console

Open browser developer tools (F12) and check the Console tab for JavaScript errors and AJAX request details.

## Security Considerations

### Data Protection
- All user data is sanitized before processing
- Authentication tokens are validated server-side
- Session data is properly managed and cleaned up

### HTTPS Requirement
- Always use HTTPS in production
- OTPless requires HTTPS for security
- Update your site URL to use HTTPS

### Rate Limiting
- Consider implementing rate limiting for magic link requests
- Monitor authentication attempts for suspicious activity

## Support

### Documentation
- [OTPless Documentation](https://docs.otpless.com)
- [Gravity Forms Documentation](https://docs.gravityforms.com)

### Community
- [WordPress Support Forums](https://wordpress.org/support/)
- [Gravity Forms Community](https://www.gravityforms.com/community/)

### Professional Support
For professional support and customizations, contact:
- [OTPless Support](https://otpless.com/support)
- [Gravity Forms Support](https://www.gravityforms.com/support/)

## Changelog

### Version 1.0.0
- Initial release
- Basic OTPless integration with Gravity Forms
- OTP authentication (WhatsApp/SMS/Email)
- User data mapping
- Admin settings panel

## License

This plugin is licensed under the GPL v2 or later.

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## Credits

- Built with [OTPless PHP SDK](https://github.com/otpless/otpless-php-auth-sdk)
- Integrated with [Gravity Forms](https://www.gravityforms.com/)
- Designed for [WordPress](https://wordpress.org/)
