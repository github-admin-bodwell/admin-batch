<?php
use PHPMailer\PHPMailer\PHPMailer;
require_once __DIR__ . '/../settings.php';
require_once __DIR__ . '/../lib/smtp-config.php';
require_once __DIR__ . '/../lib/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../lib/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../lib/PHPMailer/src/SMTP.php';

function sendEmail($from, $to, $cc, $subject, $body, $altBody = '', $bcc = []) {
    $smtp = loadBatchSmtpConfig();
    $mail = new PHPMailer();
    $mail->isSMTP();
    $mail->CharSet = "UTF-8";
    $mail->SMTPDebug = $smtp['debug'];
    $mail->Host = $smtp['host'];
    $mail->Port = $smtp['port'];
    $mail->SMTPSecure = $smtp['secure'];
    $mail->SMTPAuth = $smtp['auth'];
    $mail->Username = $smtp['username'];
    $mail->Password = $smtp['password'];


    $mail->AddEmbeddedImage('img/youtube.jpg', 'youtube');
    $mail->AddEmbeddedImage('img/instagram.jpg', 'instagram');
    $mail->AddEmbeddedImage('img/facebook.jpg', 'facebook');
    $mail->AddEmbeddedImage('img/school.png', 'school');

    $mail->setFrom($from['email'], $from['name']);
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );
    foreach($to as $row) {
        $email = $row['email'];
        $name = $row['name'];
        $mail->addAddress($email, $name);
    }
    //$mail->addCC($from['email'], $from['name']);

    if($cc) {
        foreach($cc as $row) {
            $email = $row['email'];
            $name = $row['name'];
            $mail->addCC($email, $name);
        }
    }
    
    foreach ($bcc as $row) {
        $mail->addBCC($row['email'], $row['name'] ?? '');
    }

    $mail->Subject = $subject;
    $mail->msgHTML($body);
    $mail->AltBody = $altBody;
    if(!$mail->send()) {
        return $mail->ErrorInfo;
    } else {
        return true;
    }
}
