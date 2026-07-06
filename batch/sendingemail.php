<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);


require_once __DIR__.'/sendEmailClass2.php';




$dsn = "odbc:Driver={SQL Server};Server=10.100.4.6;Database=Bodwell;Uid=web;Pwd=AJgw!cG4nw;";
// $dsn = "odbc:Driver={SQL Server};Server=10.100.0.5;Database=Bodwell;Uid=devweb;Pwd=9zQjq4WRgkFF;";

$conn = new PDO($dsn);

// Set batch size based on current queue count
$countStmt = $conn->query("SELECT COUNT(*) AS thecount FROM tblsendemail WHERE sentdate IS NULL");

$totalQueue = $countStmt->fetch(PDO::FETCH_ASSOC)['thecount'];


if ($totalQueue <= 50) {
    $batchSize = 10;
} elseif ($totalQueue <= 100) {
    $batchSize = 15;
} elseif ($totalQueue <= 200) {
    $batchSize = 20;
} elseif ($totalQueue <= 300) {
    $batchSize = 30;
} elseif ($totalQueue <= 400) {
    $batchSize = 40;
} elseif ($totalQueue <= 500) {
    $batchSize = 50;
} elseif ($totalQueue <= 600) {
    $batchSize = 60;
} elseif ($totalQueue <= 700) {
    $batchSize = 70;
} elseif ($totalQueue <= 800) {
    $batchSize = 80;
} elseif ($totalQueue <= 900) {
    $batchSize = 90;
} else {
    $batchSize = 100;
}



// Fetch and send in batches
for ($i = 1; $i <= $batchSize; $i++) {
    $stmt = $conn->query("SELECT TOP 1 * FROM tblsendemail WHERE sentdate IS NULL ORDER BY sendid");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) break;

    $sendID = $row['SendID'];
    $toList = str_replace(',', ';', $row['SendTo']);
    $isValid = true;

    // foreach (explode(';', $toList) as $email) {
    //     if (!filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
    //         $isValid = false;
    //         break;
    //     }
    // }
    // if (!$isValid) {
    //     // Send notice to admin
    //     sendEmail(
    //         ['email' => 'chanho.lee@bodwell.edu', 'name' => ''],
    //         [['email' => 'chanho.lee@bodwell.edu', 'name' => '']],
    //         [],
    //         "Invalid Email Address",
    //         "{$sendID} - {$row['SendTo']}"
    //     );
    //
    //     $conn->prepare("UPDATE tblsendemail SET sentdate = GETDATE() WHERE sendid = ?")->execute([$sendID]);
    //     continue;
    // }

    // Handle attachment
    $attachments = []; // Always defined

    if (!empty($row['SendAttach'])) {
        $filePath = $row['SendAttach'];
        if (file_exists($filePath)) {
            $attachments[] = $filePath;
        }
    }

    // Prepare email arrays
    $from = ['email' => $row['SendFrom'], 'name' => 'Bodwell High School'];
    $to = array_map(function($e) {
        return ['email' => trim($e), 'name' => ''];
    }, explode(';', $row['SendTo']));

    $cc = empty($row['Sendcc'])
    ? []
    : array_map(function($e) {
        return ['email' => trim($e), 'name' => ''];
    }, explode(';', $row['Sendcc']));

    $bcc = empty($row['Sendbcc'])
        ? []
        : array_map(function($e) {
            return ['email' => trim($e), 'name' => ''];
        }, explode(';', $row['Sendbcc']));


    sendEmail(
        $from,
        $to,
        $cc,
        $row['SendSubject'],
        $row['SendMessage'],
        $attachments
    );

    // Mark as sent
    $conn->prepare("UPDATE tblsendemail SET sentdate = GETDATE() WHERE sendid = ?")->execute([$sendID]);

    // Remove duplicates
    $deleteStmt = $conn->prepare("
        DELETE FROM tblsendemail
        WHERE sendsubject = ?
        AND sendto = ?
        AND CAST(sendmessage AS NVARCHAR(MAX)) = ?
        AND sentdate IS NULL
    ");
    $deleteStmt->execute([$row['SendSubject'], $row['SendTo'], $row['SendMessage']]);
}
