# Quick Setup Guide - OTPless Gravity Forms Integration

## 🚀 Quick Start

### 1. Upload Files
Upload all plugin files to: `/wp-content/plugins/otpless-gravity-forms-integration/`

### 2. Activate Plugin
Go to **WordPress Admin → Plugins** and activate "OTPless Gravity Forms Integration"

### 3. Get OTPless Credentials
1. Sign up at [OTPless.com](https://otpless.com)
2. Create a new application
3. Get your Client ID and Client Secret

### 4. Configure Plugin
1. Go to **Settings → OTPless**
2. Enter your Client ID and Client Secret
3. Save changes

### 5. Test Integration
1. Create or edit a Gravity Form
2. Add fields with labels: "Email", "Phone", "Name" (optional)
3. Test form submission - OTPless modal will appear

## 📁 File Structure

```
otpless-gravity-forms-integration/
├── otpless-gravity-forms-integration.php    # Main plugin
├── otpless-callback-handler.php             # Callback handler
├── install.php                              # Installation checker
├── assets/
│   ├── js/otpless-gravity-forms.js         # Frontend logic
│   └── css/otpless-gravity-forms.css       # Styling
├── otpless-php-auth-sdk/                   # OTPless PHP SDK
└── README.md                               # Full documentation
```

## ⚙️ Configuration

### Required Settings
- **Client ID**: From OTPless dashboard
- **Client Secret**: From OTPless dashboard
- **Redirect URI**: `https://yourdomain.com/wp-admin/admin-ajax.php?action=otpless_callback`

### Optional Customization
- Modify field mapping in main plugin file
- Customize modal styling in CSS file
- Add custom validation in JavaScript file

## 🔧 Troubleshooting

### Common Issues

1. **"OTPless SDK not found"**
   - Ensure SDK files are uploaded correctly
   - Check file permissions (755 for directories, 644 for files)

2. **"Invalid callback"**
   - Verify redirect URI in OTPless dashboard
   - Ensure HTTPS is enabled

3. **Magic link not received**
   - Check spam/junk folders
   - Verify email/phone format
   - Confirm OTPless credentials

4. **Form not submitting**
   - Check browser console for errors
   - Verify Gravity Forms is active
   - Check form validation

### Debug Mode
Add to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Installation Check
Run: `https://yourdomain.com/wp-content/plugins/otpless-gravity-forms-integration/install.php`

## 📱 User Experience

### Authentication Flow
1. User fills form
2. Clicks submit
3. OTPless modal appears
4. Enters email/phone
5. Selects OTP delivery method
6. Receives 6-digit OTP
7. Enters OTP to authenticate
8. Form submits automatically

### Features
- ✅ OTP authentication
- ✅ Email, WhatsApp & SMS support
- ✅ Automatic user data mapping
- ✅ Session management
- ✅ Responsive design
- ✅ Admin settings panel

## 🔒 Security

- All data is sanitized
- HTTPS required for production
- Authentication tokens validated server-side
- Session data properly managed

## 📞 Support

- **Documentation**: See README.md for full details
- **OTPless Support**: [otpless.com/support](https://otpless.com/support)
- **Gravity Forms**: [gravityforms.com/support](https://gravityforms.com/support)

## 🎯 Next Steps

1. Test with a simple form
2. Configure field mapping if needed
3. Customize styling if desired
4. Monitor authentication logs
5. Set up error notifications

---

**Need help?** Check the full README.md for detailed documentation and troubleshooting guides.
