<?php
/**
 * Email Configuration for Inventra
 * 
 * INSTRUCTIONS:
 * 1. Go to https://myaccount.google.com/security
 * 2. Enable 2-Step Verification
 * 3. Go to App passwords → Generate a new App Password for "Mail"
 * 4. Paste that 16-character password below
 */

$email_config = [
    'smtp_host'     => 'smtp.gmail.com',
    'smtp_port'     => 587,
    'smtp_user'     => 'shirishgurung1224@gmail.com',        // ← Replace with your Gmail
    'smtp_pass'     => 'lccb hata dpey hzkc',            // ← Replace with Gmail App Password
    'from_name'     => 'Inventra System',
    'from_email'    => 'shirishgurung1224@gmail.com',         // ← Same as smtp_user
];
?>
