<?php
/**
 * @author Bryan Lee <yeebwn@gmail.com>
 */

require_once  __DIR__.'/PHPMailer-5.2.22/PHPMailerAutoload.php';

function sendEmail($from, $to, $cc, $subject, $body, $altBody = '') {
    global $settings;
    $mail = new PHPMailer();
    $mail->isSMTP();
    $mail->SMTPDebug = $settings['smtp']['debug'];
    $mail->Host = $settings['smtp']['host'];
    $mail->Port = $settings['smtp']['port'];
    $mail->SMTPSecure = $settings['smtp']['secure'];
    $mail->SMTPAuth = $settings['smtp']['auth'];
    $mail->Username = $settings['smtp']['username'];
    $mail->Password = $settings['smtp']['password'];
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
    $mail->Subject = $subject;
    $mail->msgHTML($body);
    $mail->AltBody = $altBody;
    if(!$mail->send()) {
        return $mail->ErrorInfo;
    } else {
        return true;
    }
}
