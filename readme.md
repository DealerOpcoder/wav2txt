# Voicemail Processing System

**Teach your old PBX phone system new tricks.** Transcribe .wav voicemails into text and generate smart summaries using Google's Gemini AI. This PHP script processes voicemail emails and forwards formatted summaries to appropriate recipients. Compatible with Avaya IP Office R12, Voicemail Pro, SiteGround shared hosting, and Google Gemini API.

## Features

- **Audio Transcription**: Uses Google Gemini AI to transcribe voicemail audio files
- **Call Summarization**: Generates professional call summaries with caller info, purpose, and action items
- **Extension Mapping**: Routes voicemails to specific recipients based on extension numbers
- **Mobile-Friendly HTML**: Sends beautifully formatted HTML emails optimized for mobile devices
- **Auto-Reply Detection**: Skips processing auto-reply and vacation messages
- **WAV Attachment**: Includes original audio file as attachment in forwarded emails
- **Smart Fallback Routing**: If no extension/name match is found, emails automatically go to admin
- **Avaya IP Office R12 Compatible**: Designed specifically for Avaya IP Office R12 with Voicemail Pro
- **SiteGround Hosting Ready**: Optimized for SiteGround shared hosting environment
- **Google Gemini API Integration**: Uses Google's latest Gemini AI for transcription and summarization

## Requirements

- PHP 7.4 or higher
- cURL extension
- Google Gemini API key
- Avaya IP Office R12 with Voicemail Pro
- SiteGround shared hosting account
- Email server with pipe functionality

## Installation

1. Clone or download this repository
2. Copy `transcribe.php` (to your server, give it 755)
3. Configure the script with your settings

## Configuration

### 1. Google Gemini API Setup

1. **Get a Google Gemini API Key:**
   - Go to [Google AI Studio](https://aistudio.google.com/)
   - Sign in with your Google account
   - Create a new API key
   - Copy the API key

2. **Configure the API Key in the Script:**
   Replace `YOUR_GEMINI_API_KEY_HERE` with your actual Google Gemini API key:

```php
$GEMINI_API_KEY = 'your_actual_gemini_api_key_here';
```

3. **API Usage Notes:**
   - The script uses Gemini 2.5 Flash Lite model for optimal performance
   - API calls are made for both transcription and summarization in a single request
   - Monitor your API usage in Google AI Studio dashboard

### 2. Email Configuration

Update the email addresses in the script:

```php
// Error notification emails
mail('admin@yourcompany.com', 'Voicemail Processor Error', ...);

// Extension mapping
$extensions = [
    '8009' => 'user1@yourcompany.com',
    'User One' => 'user1@yourcompany.com',
    // Add your extensions and recipients
];
```

### 3. Email Headers

Update the email headers to match your domain:

```php
$headers = [
    "From: Voicemail System <vm@yourdomain.com>",
    "Reply-To: transcribe@yourdomain.com",
    // ...
];
```

## Avaya IP Office R12 + Voicemail Pro Setup

### 1. Email Configuration in Avaya Voicemail Pro

1. **Access Avaya IP Office Manager**
2. **Navigate to Voicemail Pro → Email Settings**
3. **Configure SMTP Settings for SiteGround:**
   - **SMTP Server**: `mail.yourdomain.com` (replace with your actual domain)
   - **Port**: `465` (for SSL)
   - **Authentication**: Enable
   - **Username**: Your SiteGround email address
   - **Password**: Your SiteGround email password
   - **Encryption**: TLS or SSL (recommended)

### 2. User Profile Configuration

**IMPORTANT**: Each user profile in Avaya IP Office must be configured to send voicemails to the same email address that runs the pipe script, might cause issues if you are using One-X portal.

1. **Access Avaya IP Office Manager**
2. **Navigate to User → [Select User] → Voicemail Settings**
3. **For EACH user, set the voicemail email address to**: `vm@yourdomain.com` (or whatever email address you're using for the pipe)
4. **Repeat for all users** who need voicemail processing
5. **This ensures all voicemails go to the same email address** that triggers the `transcribe.php` script

**Note**: The script will then route emails to individual recipients based on the extension mapping, but all voicemails must first go to the same pipe email address.

### 3. Email Pipe Configuration

1. **Create a new email address** in your SiteGround cPanel (e.g., `vm@yourdomain.com`)
2. **Set up email forwarding** to pipe emails to the script:
   - In cPanel, go to Email Forwarders
   - Create a forwarder: `vm@yourdomain.com` → `|/usr/local/bin/php -q /home/customer/www/yourdomain.com/public_html/transcribe.php`
   - Replace `yourdomain.com` with your actual domain

### 4. File Permissions

Ensure the script has proper permissions:
```bash
chmod 755 transcribe.php
```

## Usage

### As Email Pipe

The script is automatically triggered when Avaya Voicemail Pro sends emails to the configured address.

### Manual Testing

You can test the script by piping a raw email to it:

```bash
cat sample_email.txt | php transcribe.php
```

## Extension Mapping

The script maps both extension numbers and caller names to email addresses. Update the `$extensions` array with your organization's structure:

```php
$extensions = [
    // Extension numbers (for internal calls)
    '8009' => 'user1@yourcompany.com',
    '8070' => 'user2@yourcompany.com',
    '8040' => 'admin@yourcompany.com',
    '3101' => 'service@yourcompany.com',
    
    // Caller names (for DID calls - max 15 characters)
    'John Smith' => 'john.smith@yourcompany.com',
    'Jane Doe' => 'jane.doe@yourcompany.com',
    'Service Dept' => 'service@yourcompany.com',
    'Manager' => 'manager@yourcompany.com',
    
    // Add more mappings as needed
];
```

### Avaya IP Office R12 Mapping Strategy

1. **For Internal Calls**: Map 4-digit extension numbers to email addresses
2. **For DID Calls**: Map caller names (up to 15 characters) to email addresses
3. **Fallback**: If no match is found, emails go to `admin@yourcompany.com`
4. **Name Truncation**: Avaya will truncate names longer than 15 characters

## Subject Line Patterns

The script extracts recipient information from email subjects using these patterns:

1. `(Name > ReceivingParty)` - e.g., "Voicemail Message (John Doe > 8040) From:2251"
2. `(Name)` - e.g., "Voicemail Message (John Doe) From:2251"
3. `Name From:extension` - e.g., "John Doe From:2251"

### Avaya IP Office R12 Specific Behavior

- **DID Calls**: Shows actual caller name (up to 15 characters) in subject
- **Internal Calls**: Shows 4-digit extension number in subject
- **Name Field Limit**: Avaya limits names to 15 characters maximum
- **Email Routing**: Script handles both name-based and extension-based routing

## Output Format

The script generates HTML emails with:

- **Call Summary**: Professional summary with caller info, purpose, and action items
- **Transcription**: Full text transcription of the voicemail
- **Original Audio**: WAV file attachment
- **Mobile Optimization**: Responsive design for mobile devices

## Error Handling

- Logs auto-reply detection and skips processing
- Handles API errors gracefully
- Sends error notifications to admin email
- Validates audio file size and format

## Security Notes

- Keep your API key secure and never commit it to version control
- Use environment variables for sensitive configuration
- Regularly rotate your API keys
- Monitor API usage and costs

## Troubleshooting

### Common Issues

1. **"Pipe goes to space" error**: Usually caused by output buffering or complex email construction
2. **API key errors**: Ensure your Gemini API key is valid and has proper permissions
3. **Email formatting issues**: Check that your email server supports HTML emails
4. **Attachment problems**: Verify that audio files are properly encoded
5. **Avaya email not reaching script**: Check SMTP configuration and email forwarding setup
6. **SiteGround hosting issues**: Ensure PHP version compatibility and file permissions

### Avaya IP Office R12 + Voicemail Pro Specific Issues

1. **Emails not being sent**: Verify SMTP settings in Avaya IP Office Manager → Voicemail Pro
2. **Authentication failures**: Double-check SiteGround email credentials in Voicemail Pro settings
3. **Port blocking**: Try both port 587 (TLS) and 465 (SSL) in IP Office configuration
4. **Voicemail Pro not detecting voicemails**: Check that voicemail forwarding is enabled in IP Office
5. **Extension mapping issues**: Verify that extension numbers match your IP Office configuration
6. **Email routing problems**: 
   - Check that both extension numbers AND caller names are mapped in the `$extensions` array
   - Verify caller names are 15 characters or less (Avaya truncates longer names)
   - Test with both internal calls (shows extension) and DID calls (shows name)
7. **Voicemails not reaching script**: 
   - **CRITICAL**: Verify that ALL user profiles have their voicemail email set to the same address (e.g., `vm@yourdomain.com`)
   - Check that this email address is the one configured for the pipe script
   - If users have different email addresses, voicemails won't reach the processing script

### SiteGround Hosting Specific Issues

1. **Script not executing**: Check file permissions and PHP path
2. **Email pipe not working**: Verify email forwarder configuration in cPanel
3. **Memory limits**: SiteGround may have memory limits; check if large audio files cause issues

### Debug Mode

Enable debug logging by checking your server's error logs:

```bash
# SiteGround error logs
tail -f /home/customer/www/yourdomain.com/logs/error.log

# Or check cPanel Error Logs section
```

## License

This project is open source. Please ensure you comply with Google's Gemini API terms of service.

## Contributing

Feel free to submit issues and enhancement requests. When contributing code, please:

1. Test thoroughly
2. Follow the existing code style
3. Update documentation as needed
4. Sanitize any sensitive information

## Support

For issues and questions, please check the troubleshooting section or create an issue in the repository.
