<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader (or include PHPMailer files manually if not using Composer)
require '../vendor/autoload.php';

function sendEmail($toEmail, $toName, $subject, $htmlBody, $altBody) {
    // Retrieve SMTP credentials from environment variables or secure source
    $smtpUsername = 'mzaref360@gmail.com';
    $smtpPassword = 'nfhp lmlo xfmt stnj';
    
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();                                          // Send using SMTP
        $mail->Host       = 'smtp.gmail.com';                     // Set the SMTP server to send through
        $mail->SMTPAuth   = true;                                 // Enable SMTP authentication
        $mail->Username   = $smtpUsername;                        // SMTP username
        $mail->Password   = $smtpPassword;                        // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;       // Enable TLS encryption
        $mail->Port       = 587;                                  // TCP port to connect to

        // Recipients
        $mail->setFrom('mzaref360@gmail.com', 'ecommerce');      // Replace with your email
        $mail->addAddress($toEmail, $toName);                     // Add a recipient

        // Content
        $mail->isHTML(true);                                      // Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = $altBody;

        $mail->send();
        // echo 'Email has been sent successfully';
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}

// // Example usage
// $toEmail = 'mzaref90@gmail.com';
// $toName = 'michael';
// $subject = 'Test Email from PHPMailer';
// $htmlBody = '<h1>Test Email</h1><p>This is a test email sent using Gmail SMTP with PHPMailer.</p>';
// $altBody = 'This is the plain text version of the email content';

// sendEmail($toEmail, $toName, $subject, $htmlBody, $altBody);
