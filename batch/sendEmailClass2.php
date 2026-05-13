<?php
// echo dirname(__FILE__);
require_once '../settings.php';
require_once '../lib/PHPMailer-5.2.22/PHPMailerAutoload.php';
function cleanUserHtmlForEmail($html)
{
    $html = (string)$html;

    // Normalize <br> variations
    $html = preg_replace('/<\s*br\s*\/?\s*>/i', "<br>", $html);

    // Remove control characters (incl. NULL)
    $html = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $html);

    // Remove scripts/styles completely
    $html = preg_replace('#<\s*(script|style)\b[^>]*>.*?<\s*/\s*\1\s*>#is', '', $html);

    // Remove HTML comments (often pasted from Word)
    $html = preg_replace('/<!--.*?-->/s', '', $html);

    // Allow a controlled set of tags (includes tables)
    $allowed = '<br><p><div><span><b><strong><i><em><u>'
             . '<table><thead><tbody><tfoot><tr><td><th>'
             . '<ul><ol><li>'
             . '<a>';

    $html = strip_tags($html, $allowed);

    // Remove event handler attributes like onclick=, onload=, etc.
    $html = preg_replace('/\son\w+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $html);

    // Remove javascript: links
    $html = preg_replace('/href\s*=\s*("|\')\s*javascript:.*?\1/i', 'href="#"', $html);

    return $html;
}


function sendEmail($from, $to, $cc, $subject, $body, $attachments = [], $altBody = '') {
    global $settings;

    $mail = new PHPMailer();
    $mail->isSMTP();
    $mail->isHTML(true);
    $mail->CharSet  = 'UTF-8';
    $mail->Encoding = 'quoted-printable';

    $mail->SMTPDebug = 2;
    $mail->Host = $settings['smtp']['host'];
    $mail->Port = $settings['smtp']['port'];
    $mail->SMTPSecure = $settings['smtp']['secure'];
    $mail->SMTPAuth = $settings['smtp']['auth'];
    $mail->Username = $settings['smtp']['username'];
    $mail->Password = $settings['smtp']['password'];

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
        $mail->addAddress($row['email'], $row['name']);
    }

    if($cc) {
        foreach($cc as $row) {
            $mail->addCC($row['email'], $row['name']);
        }
    }

    // Attachments
    if (!empty($attachments) && is_array($attachments)) {
        foreach ($attachments as $file) {
            if (file_exists($file)) {
                $mail->addAttachment($file);
            }
        }
    }




    $mail->Subject = $subject;

    $safe = cleanUserHtmlForEmail($body);


    $mail->Body = '<!doctype html><html><body style="margin:0;padding:0;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
      <tr>
        <td style="padding:16px;font-family:Arial,sans-serif;font-size:14px;line-height:20px;color:#000;">'
          . $safe .
        '</td>
      </tr>
    </table>
    </body></html>';
    // $mail->msgHTML($safe);


    $mail->AltBody = $altBody;
    // $mail->Body     = '<!doctype html><html><body>' . $body . '</body></html>';
    // $mail->AltBody  = strip_tags(str_replace('<br>', "\n", $body));
    if (!$mail->send()) {
        return $mail->ErrorInfo;
    } else {
        return true;
    }
}
