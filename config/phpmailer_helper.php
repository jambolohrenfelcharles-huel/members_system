
<?php
require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/email_config.php';

function sendMailPHPMailer($to, $subject, $bodyHtml, $smtpUser = null, $smtpPass = null, $fromEmail = null, $fromName = null) {
    $config = getEmailConfig();
    $mail = new PHPMailer(true);
    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = $config['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpUser ?: $config['smtp_username'];
        $mail->Password   = $smtpPass ?: $config['smtp_password'];
        $mail->SMTPSecure = $config['smtp_encryption'];
        $mail->Port       = $config['smtp_port'];
        $mail->CharSet    = $config['charset'];
    $finalFromName = $fromName ?: $config['from_name'];
    $mail->setFrom($fromEmail ?: $config['from_address'], $finalFromName);
    $mail->addReplyTo($config['reply_to'], $finalFromName);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $bodyHtml;
        $mail->AltBody = strip_tags($bodyHtml);
        $mail->Priority = $config['priority'];
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('PHPMailer Error: ' . $mail->ErrorInfo);
        return false;
    }
}
