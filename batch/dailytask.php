<?php

require_once __DIR__ . '/sendEmailClass.php';
$subject = 'daily test at 11pm';
$body = 'daily test body';

$sendEmail = sendEmail(
    ['email' => 'no-reply@bodwell.edu', 'name' => 'Bodwell System'],
    [['email' => 'chanho.lee@bodwell.edu', 'name' => '']],
    [],
    $subject,
    $body
);

 ?>
