#!/usr/local/bin/php -q
<?php
// Clean Gemini Voicemail Processor - Consolidated Debug Version
// This version sends only ONE debug email with all the information

// Configuration
$GEMINI_API_KEY = 'YOUR_GEMINI_API_KEY_HERE'; // Replace with your actual API key
$GEMINI_API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent';

// Read the email from stdin
$raw_email = file_get_contents('php://stdin');

if (empty($raw_email)) {
    mail('admin@yourcompany.com', 'Voicemail Processor Error', 'No email data received from stdin', 'From: Voicemail System <vm@yourdomain.com>' . "\r\n" . 'Reply-To: transcribe@yourdomain.com');
    exit(1);
}

// Parse the email
$email_data = parseEmail($raw_email);

// Check for auto-reply/vacation messages and exit if found
$auto_reply_headers = [
    'x-autoreply',
    'auto-submitted',
    'precedence'
];

foreach ($auto_reply_headers as $header) {
    $value = $email_data['headers'][$header] ?? '';
    if (stripos($value, 'yes') !== false || 
        stripos($value, 'auto-replied') !== false || 
        stripos($value, 'bulk') !== false) {
        // Log the auto-reply detection and exit silently
        error_log("Auto-reply detected: $header = $value - skipping processing");
        exit(0);
    }
}

// Extract name from subject
$name = extractNameFromSubject($email_data['subject']);

if (empty($name)) {
    mail('admin@yourcompany.com', 'Voicemail Processor Error', "Could not extract name from subject: " . $email_data['subject'], 'From: Voicemail System <vm@yourdomain.com>' . "\r\n" . 'Reply-To: transcribe@yourdomain.com');
    exit(1);
}


// Extension mapping - customize this for your organization
$extensions = [
    '8009' => 'user1@yourcompany.com',
    'User One' => 'user1@yourcompany.com',
    '8070' => 'user2@yourcompany.com',
    'User Two' => 'user2@yourcompany.com',
    '8040' => 'admin@yourcompany.com',
    'Admin User' => 'admin@yourcompany.com',
    '3101' => 'service@yourcompany.com',  
    'Service Dept' => 'service@yourcompany.com',  
    '3121' => 'service2@yourcompany.com',  
    'Service Dept 2' => 'service2@yourcompany.com',  
    '3019' => 'service3@yourcompany.com',  
    'Service Dept 3' => 'service3@yourcompany.com',  
    '3004' => 'parts@yourcompany.com',  
    'Parts Dept' => 'parts@yourcompany.com',  
    '3023' => 'parts2@yourcompany.com',  
    'Parts Dept 2' => 'parts2@yourcompany.com',  
    '2251' => 'manager@yourcompany.com',
    'Manager' => 'manager@yourcompany.com',
    '8011' => 'user3@yourcompany.com',
    'User Three' => 'user3@yourcompany.com',
];

$target_email = $extensions[$name] ?? 'admin@yourcompany.com';

// Process audio attachments and generate summary in one API call
$transcription = '';
$call_summary = '';
if (!empty($email_data['attachments'])) {
    $result = processAudioWithSummary($email_data['attachments']);
    $transcription = $result['transcription'];
    $call_summary = $result['summary'];
}

// Send HTML email to mapped recipient with WAV attachment
$mail_sent = sendDebugEmailWithAttachment($email_data, $name, $target_email, $transcription, $call_summary);


exit($mail_sent ? 0 : 1);

/**
 * Generate HTML email content for mobile compatibility
 */
function generateHtmlEmail($email_data, $name, $target_email, $transcription, $call_summary, $mail_sent) {
    // Set timezone to Pacific Time
    date_default_timezone_set('America/Los_Angeles');
    $processed_time = date('Y-m-d g:i A T');
    $transcription_status = $transcription ? "SUCCESS (" . strlen($transcription) . " chars)" : "NONE";
    $email_status = $mail_sent ? "SUCCESS" : "FAILED";
    $attachment_count = count($email_data['attachments']);
    
    // Create preview text for iPhone (first 100 characters of transcription)
    $preview_text = '';
    if ($transcription) {
        $preview_text = strlen($transcription) > 100 ? substr($transcription, 0, 100) . '...' : $transcription;
    } else {
        $preview_text = 'New voicemail message';
    }
    
    $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New voicemail message</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }
        .content {
            padding: 20px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
            margin-bottom: 25px;
        }
        .summary-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #667eea;
        }
        .summary-label {
            font-weight: 600;
            color: #555;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .summary-value {
            color: #333;
            font-size: 16px;
        }
        .status-success {
            color: #28a745;
            font-weight: 600;
        }
        .status-failed {
            color: #dc3545;
            font-weight: 600;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            background: #e9ecef;
            color: #495057;
            padding: 12px 15px;
            margin: 0 0 15px 0;
            font-weight: 600;
            font-size: 16px;
            border-radius: 4px;
        }
        .transcription-text {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #dee2e6;
            font-style: italic;
            line-height: 1.7;
            white-space: nowrap;
            overflow-x: auto;
        }
        .call-summary {
            background: #e8f4fd;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #bee5eb;
            line-height: 1.6;
            font-weight: 500;
        }
        .attachment-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .attachment-item {
            background: #f8f9fa;
            padding: 12px 15px;
            margin-bottom: 8px;
            border-radius: 4px;
            border-left: 3px solid #6c757d;
        }
        .attachment-filename {
            font-weight: 600;
            color: #495057;
        }
        .attachment-type {
            color: #6c757d;
            font-size: 14px;
        }
        .footer {
            background: #f8f9fa;
            padding: 15px 20px;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
            border-top: 1px solid #dee2e6;
        }
        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            .header h1 {
                font-size: 20px;
            }
            .content {
                padding: 15px;
            }
            .summary-item {
                padding: 12px;
            }
        }
    </style>
</head>
<body>
    <!-- Hidden preview text for iPhone email clients -->
    <div style="display: none; color: #333; font-size: 14px; margin: 0; padding: 0; line-height: 1.4;">' . htmlspecialchars($preview_text) . '</div>
    
    <div class="container">
        <div class="header">
            <h1>📞 New voicemail message</h1>
        </div>
        
        <div class="content">
            <div class="summary-grid">
                <div class="summary-item">
                <div class="summary-label">CID: ' . htmlspecialchars(str_replace('Voicemail Message ', '', $email_data['subject'])) . '</div>    
                <div class="summary-label">Date: ' . $processed_time . '</div>
                     </div>
            </div>';

    // Add call summary section if available
    if ($call_summary) {
        // Convert markdown bold syntax to HTML
        $formatted_summary = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', htmlspecialchars($call_summary));
        $html .= '
            <div class="section">
                <h2 class="section-title">📋 Call Summary</h2>
                <div class="call-summary">' . nl2br($formatted_summary) . '</div>
            </div>';
    }

    // Add transcription section if available
if ($transcription) {
        $html .= '
            <div class="section">
                <h2 class="section-title">📝 Transcription Text</h2>
                <div class="transcription-text">' . htmlspecialchars($transcription) . '</div>
            </div>';
    }

    // Close content and container divs
    $html .= '
        </div>
        
        <div class="footer">
            Generated by Voicemail Processing System
        </div>
    </div>
</body>
</html>';

    return $html;
}

/**
 * Send debug email with WAV attachment
 */
function sendDebugEmailWithAttachment($email_data, $name, $target_email, $transcription, $call_summary) {
    // Find the WAV file attachment
    $wav_attachment = null;
    foreach ($email_data['attachments'] as $attachment) {
        if (isAudioFile($attachment)) {
            $wav_attachment = $attachment;
            break;
        }
    }
    
    // Generate boundary for multipart email
    $boundary = md5(uniqid(time()));
    
    // Generate HTML content
    $html_content = generateHtmlEmail($email_data, $name, $target_email, $transcription, $call_summary, false);
    
    // Start building the email body
    $email_body = "--$boundary\r\n";
    $email_body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $email_body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $email_body .= $html_content . "\r\n";
    
    // Add WAV attachment if found
    if ($wav_attachment) {
        $email_body .= "--$boundary\r\n";
        $email_body .= "Content-Type: " . $wav_attachment['content-type'] . "\r\n";
        $email_body .= "Content-Transfer-Encoding: base64\r\n";
        $email_body .= "Content-Disposition: attachment; filename=\"" . ($wav_attachment['filename'] ?: 'voicemail.wav') . "\"\r\n\r\n";
        
        // Get the audio content and encode it
        $audio_content = $wav_attachment['content'];
        $encoding = $wav_attachment['encoding'] ?? '';
        
        if ($encoding === 'base64') {
            // Content is already base64 encoded
            $email_body .= $audio_content;
        } else {
            // Content is raw binary, encode it to base64
            $email_body .= chunk_split(base64_encode($audio_content));
        }
        
        $email_body .= "\r\n";
    }
    
    // Close the boundary
    $email_body .= "--$boundary--\r\n";
    
    // Set headers
    $headers = [
        "From: Voicemail System <vm@yourdomain.com>",
        "Reply-To: transcribe@yourdomain.com",
        "MIME-Version: 1.0",
        "Content-Type: multipart/mixed; boundary=\"$boundary\""
    ];
    
    // Send the email - encode subject to handle special characters
    $email_subject = !empty($email_data['subject']) ? $email_data['subject'] : 'Voicemail Processing Summary';
    
    // Encode subject for email headers (handles special characters like (), >, :)
    $encoded_subject = '=?UTF-8?B?' . base64_encode($email_subject) . '?=';
    
    error_log("DEBUG: Original subject: '" . $email_subject . "'");
    error_log("DEBUG: Encoded subject: '" . $encoded_subject . "'");
    
    $result = mail(
        $target_email,
        $encoded_subject,
        $email_body,
        implode("\r\n", $headers)
    );
    
    return $result;
}



/**
 * Parse email and extract headers, body, and attachments
 */
function parseEmail($raw_email) {
    $email_data = [
        'headers' => [],
        'body' => '',
        'attachments' => []
    ];
    
    // Find the first empty line to separate headers from body
    $header_end = strpos($raw_email, "\n\n");
    if ($header_end === false) {
        $header_end = strpos($raw_email, "\r\n\r\n");
        if ($header_end === false) {
            return $email_data; // No body found
        }
        $header_end += 4; // Skip \r\n\r\n
    } else {
        $header_end += 2; // Skip \n\n
    }
    
    $header_section = substr($raw_email, 0, $header_end);
    $body_section = substr($raw_email, $header_end);
    
    // Parse headers (handle multi-line headers)
    $header_lines = explode("\n", $header_section);
    $current_header = '';
    $current_value = '';
    
    foreach ($header_lines as $line) {
        $line = rtrim($line, "\r");
        
        // Check if this is a continuation line (starts with space or tab)
        if (preg_match('/^[ \t]/', $line)) {
            $current_value .= ' ' . trim($line);
        } else {
            // Save previous header if exists
            if ($current_header) {
                $email_data['headers'][strtolower($current_header)] = $current_value;
            }
            
            // Start new header
            if (strpos($line, ':') !== false) {
                $parts = explode(':', $line, 2);
                $current_header = trim($parts[0]);
                $current_value = trim($parts[1]);
            }
        }
    }
    
    // Save last header
    if ($current_header) {
        $email_data['headers'][strtolower($current_header)] = $current_value;
    }
    
    // Extract subject from headers
    $email_data['subject'] = $email_data['headers']['subject'] ?? '';
    
    // Check if this is a multipart message
    $content_type = $email_data['headers']['content-type'] ?? '';
    if (strpos($content_type, 'multipart/') === 0) {
        // Extract boundary from Content-Type header
        if (preg_match('/boundary="([^"]+)"/', $content_type, $matches)) {
            $boundary = $matches[1];
            $email_data['attachments'] = parseMultipartBody($body_section, $boundary);
        }
    } else {
        // Single part message
        $email_data['body'] = $body_section;
    }
    
    return $email_data;
}

/**
 * Parse multipart MIME body to extract attachments
 */
function parseMultipartBody($body, $boundary) {
    $attachments = [];
    $boundary_marker = '--' . $boundary;
    
    // Split by boundary markers
    $parts = explode($boundary_marker, $body);
    
    foreach ($parts as $part) {
        $part = trim($part);
        if (empty($part) || $part === '--') {
            continue; // Skip empty parts and final boundary
        }
        
        // Find the first empty line to separate headers from content
        $header_end = strpos($part, "\n\n");
        if ($header_end === false) {
            $header_end = strpos($part, "\r\n\r\n");
            if ($header_end === false) {
                continue; // No content found
            }
            $header_end += 4;
        } else {
            $header_end += 2;
        }
        
        $part_headers = substr($part, 0, $header_end);
        $part_content = substr($part, $header_end);
        
        // Parse part headers
        $attachment = [
            'content-type' => '',
            'filename' => '',
            'content' => '',
            'encoding' => ''
        ];
        
        $header_lines = explode("\n", $part_headers);
        foreach ($header_lines as $header_line) {
            $header_line = trim($header_line);
            if (strpos($header_line, 'Content-Type:') === 0) {
                $attachment['content-type'] = trim(substr($header_line, 13));
            } elseif (strpos($header_line, 'Content-Transfer-Encoding:') === 0) {
                $attachment['encoding'] = trim(substr($header_line, 26));
            } elseif (strpos($header_line, 'Content-Disposition:') === 0) {
                if (preg_match('/filename="?([^"]+)"?/', $header_line, $matches)) {
                    $attachment['filename'] = $matches[1];
                }
            } elseif (strpos($header_line, 'name=') !== false) {
                // Alternative filename location in Content-Type
                if (preg_match('/name="?([^"]+)"?/', $header_line, $matches)) {
                    $attachment['filename'] = $matches[1];
                }
            }
        }
        
        // Only process if this looks like an attachment (not just text)
        if (!empty($attachment['content-type']) && 
            (strpos($attachment['content-type'], 'text/plain') === false || 
             !empty($attachment['filename']))) {
            
            // Clean up the content - remove any trailing boundary markers
            $part_content = rtrim($part_content, "\r\n");
            if (substr($part_content, -2) === '--') {
                $part_content = substr($part_content, 0, -2);
            }
            
            $attachment['content'] = $part_content;
            $attachments[] = $attachment;
        }
    }
    
    return $attachments;
}

/**
 * Extract name from subject line
 */
function extractNameFromSubject($subject) {
    // Pattern 1: (Name > ReceivingParty) - matches "Voicemail Message (Allan T > 8040) From:2251"
    if (preg_match('/\([^>]+ > ([^)]+)\)/', $subject, $matches)) {
        return trim($matches[1]);
    }
    
    // Pattern 2: (Name)
    if (preg_match('/\(([^)]+)\)/', $subject, $matches)) {
        return trim($matches[1]);
    }
    
    // Pattern 3: Name From:extension
    if (preg_match('/^([^F]+) From:/', $subject, $matches)) {
        return trim($matches[1]);
    }
    
    return '';
}

/**
 * Process audio attachments with Gemini AI
 */

/**
 * Check if attachment is an audio file
 */
function isAudioFile($attachment) {
    $audio_types = ['audio/mpeg', 'audio/wav', 'audio/mp3', 'audio/m4a', 'audio/ogg', 'audio/vnd.wave'];
    $content_type = $attachment['content-type'] ?? '';
    $filename = $attachment['filename'] ?? '';
    
    // Check by content type
    foreach ($audio_types as $audio_type) {
        if (strpos($content_type, $audio_type) !== false) {
            return true;
        }
    }
    
    // Check by filename extension
    $audio_extensions = ['.wav', '.mp3', '.m4a', '.ogg', '.mp4', '.aac'];
    foreach ($audio_extensions as $ext) {
        if (stripos($filename, $ext) !== false) {
            return true;
        }
    }
    
    return false;
}

/**
 * Transcribe audio using Gemini AI
 */

/**
 * Generate call summary using Gemini AI
 */

/**
 * Process audio and generate both transcription and summary in one API call
 */
function processAudioWithSummary($attachments) {
    global $GEMINI_API_KEY, $GEMINI_API_URL;
    
    if (empty($GEMINI_API_KEY) || $GEMINI_API_KEY === 'YOUR_GEMINI_API_KEY_HERE') {
        return ['transcription' => 'Transcription unavailable: API key not configured', 'summary' => 'Summary unavailable: API key not configured'];
    }
    
    // Find audio attachment
    $audio_attachment = null;
    foreach ($attachments as $attachment) {
        if (isAudioFile($attachment)) {
            $audio_attachment = $attachment;
            break;
        }
    }
    
    if (!$audio_attachment) {
        return ['transcription' => '', 'summary' => ''];
    }
    
    // Validate audio content
    $audio_content = $audio_attachment['content'];
    if (empty($audio_content)) {
        return ['transcription' => 'Transcription failed: Empty audio content', 'summary' => 'Summary failed: Empty audio content'];
    }
    
    // Check if content is already base64 encoded
    $encoding = $audio_attachment['encoding'] ?? '';
    $is_base64_encoded = ($encoding === 'base64');
    
    if ($is_base64_encoded) {
        $raw_audio = base64_decode($audio_content);
        if ($raw_audio === false) {
            return ['transcription' => 'Transcription failed: Invalid base64 encoding', 'summary' => 'Summary failed: Invalid base64 encoding'];
        }
        $audio_base64 = base64_encode($raw_audio);
    } else {
        $audio_base64 = base64_encode($audio_content);
    }
    
    $original_mime_type = $audio_attachment['content-type'] ?? 'audio/mpeg';
    
    // Convert MIME types to Gemini-compatible formats
    $mime_type_mapping = [
        'audio/vnd.wave' => 'audio/wav',
        'audio/wave' => 'audio/wav',
        'audio/x-wav' => 'audio/wav',
    ];
    
    $mime_type = $mime_type_mapping[$original_mime_type] ?? 'audio/wav';
    
    // Check file size limits (Gemini has a 20MB limit)
    $file_size_mb = strlen($audio_base64) / (1024 * 1024);
    if ($file_size_mb > 20) {
        return ['transcription' => 'Transcription failed: File too large (' . round($file_size_mb, 2) . 'MB)', 'summary' => 'Summary failed: File too large (' . round($file_size_mb, 2) . 'MB)'];
    }
    
    // Prepare request for Gemini - both transcription and summary in one call
    $request_data = [
        'contents' => [
            [
                'parts' => [
                    [
                        'text' => 'Please transcribe this audio file and then provide a concise summary.

**📋 Call Summary**
**Caller Name:** [name or company if mentioned]
**Contact Info:** [phone/email if available]
**Purpose of Call:** [brief summary]
**Action Required:** [if any]
**Tone:** [positive, neutral, negative, etc.]

**📝 Transcription Text**
[transcription here - NO timestamps, just the spoken words]

IMPORTANT: For the transcription, provide ONLY the spoken words without any timestamps, speaker labels, or time markers. Just the clean conversation text.

Format with minimal whitespace and use **bold** for labels. Keep it concise and professional.'
                    ],
                    [
                        'inline_data' => [
                            'mime_type' => $mime_type,
                            'data' => $audio_base64
                        ]
                    ]
                ]
            ]
        ]
    ];
    
    // Make API call to Gemini
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $GEMINI_API_URL . '?key=' . $GEMINI_API_KEY);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'User-Agent: VoicemailProcessor/1.0'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200) {
        return ['transcription' => 'Transcription failed: HTTP ' . $http_code, 'summary' => 'Summary failed: HTTP ' . $http_code];
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        $full_response = $result['candidates'][0]['content']['parts'][0]['text'];
        
        // Try to separate transcription and summary
        $transcription = '';
        $summary = '';
        
        // Check for format with emojis and bold markers: **📋 Call Summary** and **📝 Transcription Text**
        if (preg_match('/\*\*📋 Call Summary\*\*(.+?)\*\*📝 Transcription Text\*\*(.+)/is', $full_response, $matches)) {
            $summary = trim($matches[1]);
            $transcription = trim($matches[2]);
        }
        // Check for format without emojis but with bold markers: **Call Summary** and **Transcription Text**
        elseif (preg_match('/\*\*Call Summary\*\*(.+?)\*\*Transcription Text\*\*(.+)/is', $full_response, $matches)) {
            $summary = trim($matches[1]);
            $transcription = trim($matches[2]);
        }
        // Check for format with emojis but without bold markers: 📋 Call Summary and 📝 Transcription Text
        elseif (preg_match('/📋 Call Summary(.+?)📝 Transcription Text(.+)/is', $full_response, $matches)) {
            $summary = trim($matches[1]);
            $transcription = trim($matches[2]);
        }
        // Check for format without emojis and without bold markers: Call Summary and Transcription Text
        elseif (preg_match('/Call Summary(.+?)Transcription Text(.+)/is', $full_response, $matches)) {
            $summary = trim($matches[1]);
            $transcription = trim($matches[2]);
        }
        // Check for ## Transcription format with --- separator
        elseif (preg_match('/##\s*Transcription\s*\n(.+?)\n---\s*\n(.+)/is', $full_response, $matches)) {
            $transcription = trim($matches[1]);
            $summary = trim($matches[2]);
        }
        // Check for ## Transcription format without --- separator
        elseif (preg_match('/##\s*Transcription\s*\n(.+?)(?:\n\s*)?(?:##|Call Summary|Summary|Caller Name)/is', $full_response, $matches)) {
            $transcription = trim($matches[1]);
            $summary = trim(substr($full_response, strpos($full_response, '##', strpos($full_response, '##') + 1) ?: strpos($full_response, 'Call Summary') ?: strpos($full_response, 'Summary')));
        }
        // Check for explicit section headers
        elseif (preg_match('/^(.+?)(?:\n\s*)?(?:Call Summary|Summary|Caller Name|Contact Info|Purpose of Call|Action Required|Tone)/is', $full_response, $matches)) {
            $transcription = trim($matches[1]);
            $summary = trim(substr($full_response, strlen($matches[1])));
        }
        // Check for "Transcription:" followed by content
        elseif (preg_match('/Transcription[^:]*:\s*(.+?)(?:\n\s*)?(?:Call Summary|Summary|Caller Name)/is', $full_response, $matches)) {
            $transcription = trim($matches[1]);
            $summary = trim(substr($full_response, strpos($full_response, 'Call Summary') ?: strpos($full_response, 'Summary')));
        }
        // Fallback: look for any summary indicators
        else {
            $summary_indicators = ['Caller Name', 'Contact Info', 'Purpose of Call', 'Action Required', 'Tone', 'Call Summary', 'Summary'];
            $summary_start = -1;
            
            foreach ($summary_indicators as $indicator) {
                $pos = stripos($full_response, $indicator);
                if ($pos !== false && ($summary_start === -1 || $pos < $summary_start)) {
                    $summary_start = $pos;
                }
            }
            
            if ($summary_start !== -1) {
                $transcription = trim(substr($full_response, 0, $summary_start));
                $summary = trim(substr($full_response, $summary_start));
            } else {
                // If we can't separate, treat the whole response as transcription
                $transcription = $full_response;
                $summary = 'Summary: Unable to separate summary from transcription';
            }
        }
        
        // Clean up the transcription (remove quotes and extra formatting)
        $transcription = preg_replace('/^["\']|["\']$/', '', $transcription); // Remove surrounding quotes
        $transcription = preg_replace('/^(?:Transcription[^:]*:|##\s*Transcription\s*|\*\*📝 Transcription Text\*\*)\s*/i', '', $transcription); // Remove headers
        $transcription = trim($transcription);
        
        // Clean up the summary (remove ALL duplicate headers)
        $summary = preg_replace('/^\*\*📋 Call Summary\*\*\s*/i', '', $summary); // Remove bold header
        $summary = preg_replace('/^📋 Call Summary\s*/i', '', $summary); // Remove emoji header
        $summary = preg_replace('/^📋\s*/i', '', $summary); // Remove just the emoji
        $summary = trim($summary);
        
        // Don't add any header back - the HTML template already has the header
        
        return ['transcription' => $transcription, 'summary' => $summary];
    }
    
    // Check for alternative response structures
    if (isset($result['candidates'][0]['content']['text'])) {
        return ['transcription' => $result['candidates'][0]['content']['text'], 'summary' => 'Summary: Unable to separate summary from transcription'];
    }
    
    return ['transcription' => 'Transcription failed: Invalid response format', 'summary' => 'Summary failed: Invalid response format'];
}
?>
