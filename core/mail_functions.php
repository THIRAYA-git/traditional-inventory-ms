<?php
// PHPMailer கோப்புகளை இணைத்தல் (Path based on core folder)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// core போல்டரில் இருந்து ஒரு படி மேலே சென்று PHPMailer-ஐ எடுக்கவும்
require __DIR__ . '/../PHPMailer/src/Exception.php';
require __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../PHPMailer/src/SMTP.php';

function sendInventoEmail($toEmail, $subject, $messageBody) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'your-email@gmail.com'; // உங்கள் Email
        $mail->Password   = 'your-app-password';   // Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('your-email@gmail.com', 'InventoSmart');
        $mail->addAddress($toEmail);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $messageBody;

        return $mail->send();
    } catch (Exception $e) {
        // Error ஏற்பட்டால் அதை லாக் செய்யலாம்
        error_log("Mail Error: " . $mail->ErrorInfo);
        return false;
    }
}