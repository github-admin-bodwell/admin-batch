<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set("America/Vancouver");
require_once __DIR__ . '/sendEmailClass.php';

$dsn = "odbc:Driver={SQL Server};Server=10.100.4.6;Database=Bodwell;Uid=web;Pwd=AJgw!cG4nw;";
$pdo = new PDO($dsn);
// Check day/time is Monday 1:05am
$now = new DateTime();
// if ($now->format('N') != 1 || $now->format('H:i') != '01:05') {
//     exit;
// }
// Get group-based emails from inc_email.cfm logic
function getGroupEmails($pdo, $groupCode) {
    $emails = array();
    $stmt = $pdo->prepare("SELECT Email3 FROM tblStaff WHERE GroupTeam LIKE ? AND currentstaff = 'Y' AND Email3 <> ''");
    if ($stmt && $stmt->execute(["%$groupCode%"])) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($row['Email3'])) {
                $emails[] = $row['Email3'];
            }
        }
    }
    return $emails;
}

function parseRecipientsFromString($list, $defaultName = '') {
    if (!is_string($list) || $list === '') return array();
    // split by ; or , and trim
    $parts = preg_split('/[;,]+/', $list);
    $uniq  = array(); // dedupe by lowercase email
    $out   = array();

    foreach ($parts as $p) {
        $email = trim($p);
        if ($email === '') continue;
        // (optional) validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) continue;
        $key = strtolower($email);
        if (isset($uniq[$key])) continue; // skip duplicates
        $uniq[$key] = true;
        $out[] = array('email' => $email, 'name' => $defaultName);
    }
    return $out;
}

function fetchCounselors(PDO $pdo, $groupLike) {
    $stmt = $pdo->prepare("
        SELECT FirstName, Email3
        FROM tblStaff
        WHERE GroupTeam LIKE ? AND currentstaff = 'Y'
        ORDER BY FirstName, LastName
    ");
    $stmt->execute([$groupLike]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$msps1 = fetchCounselors($pdo, '%1027%');
$msps2 = fetchCounselors($pdo, '%1028%');
$msps1Names = array_map('mb_strtolower', array_column($msps1, 'FirstName'));
$msps2Names = array_map('mb_strtolower', array_column($msps2, 'FirstName'));


$MSP1Email     = implode(';', getGroupEmails($pdo, '1019'));
$MSP2Email     = implode(';', getGroupEmails($pdo, '1020'));
$MSP3Email     = implode(';', getGroupEmails($pdo, '1021'));
$MSPbccEmail   = implode(';', getGroupEmails($pdo, '1022'));
$Email1034     = implode(';', getGroupEmails($pdo, '1034'));
$Email1053     = implode(';', getGroupEmails($pdo, '1053'));
// 1. Get counselors
$counselors = $pdo->query("SELECT FirstName, LastName, Email3 FROM tblStaff WHERE SchoolID = 'BHS' AND PositionTitle LIKE '%Counselor%' AND CurrentStaff = 'Y' ORDER BY FirstName, LastName")->fetchAll(PDO::FETCH_ASSOC);
foreach ($counselors as $counselor) {
    $first = $counselor['FirstName'];
    $last = $counselor['LastName'];
    $email3 = $counselor['Email3'];
    $fullname = "$first $last";


    // echo $fullname;
    // Visa & MSP expiry
    $stmt = $pdo->prepare("
        SELECT s.StudentID, s.LastName, s.FirstName, s.EnglishName, s.Sex, s.Origin, s.Counselor, s.DOB,
               s.VisaExpiry, s.ReVisaExpiry, s.Visaexpiryna, s.VisaSubmission, s.Revisaexpiryna,
               s.PassportExpiry, c.CName,
               i.ExpireDate, i.expectedterm, i.CancelDate, i.PEndDate, i.M6, i.OneSemester,
               ct.SICOther
        FROM tblBHSStudent s
        INNER JOIN tblBHSStudentInfo i ON i.StudentID = s.StudentID
        INNER JOIN tblCountry c ON s.Origin = c.CID
        INNER JOIN tblBHSStudentContact ct ON s.StudentID = ct.StudentID
        WHERE s.CurrentStudent = 'Y'
          AND s.Citizenship = 'INTERNATIONAL'
          AND ct.ContactTab = 'STD'
          AND s.Counselor = ?
          AND (
                s.VisaExpiry < DATEADD(MONTH, 5, GETDATE()) OR
                s.ReVisaExpiry < DATEADD(MONTH, 5, GETDATE()) OR
                i.ExpireDate < DATEADD(MONTH, 1, GETDATE())
          )
        ORDER BY s.Counselor, s.VisaExpiry, i.ExpireDate
    ");
    $stmt->execute([$fullname]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($students) > 0) {
        // Determine MSP group
        $firstLower = mb_strtolower($first);

        if (in_array($firstLower, $msps1Names, true)) {
            $extra = $MSP1Email;
        } elseif (in_array($firstLower, $msps2Names, true)) {
            $extra = $MSP2Email;
        } else {
            $extra = $MSP3Email;
        }
       $combined = "$email3;$extra";

       // Convert to array and filter out blanks
       $emailList = array_filter(array_map('trim', explode(';', $combined)));

       // Convert to array of ['email' => ..., 'name' => ...]
       $to = [];
       foreach ($emailList as $address) {
           $to[] = ['email' => $address, 'name' => ''];
       }
        $subject = "Current International Student Visa & MSP Expiration Date Notice";
        $body = "<p>Dear $first,<br><br>The following current international student's visa or MSP has been or will be expired.<br><br>";
        $body .= "<table border='1' cellspacing='0' cellpadding='0' style='font-size:10pt;'><tr align='center'><td>Student ID</td><td>Student Name</td><td>Sex</td><td>DOB</td><td>Origin</td><td>Counsellor</td><td>Study Permit Expiry</td><td>Submission Date</td><td>Re-entry Visa Expiry</td><td>Passport Expiry</td><td>MSP Expiry</td><td>Cancel Date</td><td>P.I. Expiry</td><td>Self-arranged</td><td>Expected Term</td><td>1 Sem</td><td>PR Info</td></tr>";
        foreach ($students as $s) {
            $body .= "<tr><td>{$s['StudentID']}</td><td>{$s['LastName']}, {$s['FirstName']} {$s['EnglishName']}</td><td>{$s['Sex']}</td><td>".date('Y-m-d', strtotime($s['DOB']))."</td><td>".substr($s['CName'], 0, 20)."</td><td>{$s['Counselor']}</td>";
            $body .= "<td>" . ($s['Visaexpiryna'] === 'N/A' ? 'N/A' : date('Y-m-d', strtotime($s['VisaExpiry']))) . "</td>";
            $body .= "<td>" . ($s['VisaSubmission'] ? date('Y-m-d', strtotime($s['VisaSubmission'])) : '') . "</td>";
            $body .= "<td>" . ($s['Revisaexpiryna'] === 'N/A' ? 'N/A' : date('Y-m-d', strtotime($s['ReVisaExpiry']))) . "</td>";
            $body .= "<td>" . date('Y-m-d', strtotime($s['PassportExpiry'])) . "</td>";
            $body .= "<td>" . date('Y-m-d', strtotime($s['ExpireDate'])) . "</td>";
            $body .= "<td>" . date('Y-m-d', strtotime($s['CancelDate'])) . "</td>";
            $body .= "<td>" . date('Y-m-d', strtotime($s['PEndDate'])) . "</td>";
            $body .= "<td>{$s['M6']}</td><td>{$s['expectedterm']}</td><td>{$s['OneSemester']}</td><td>{$s['SICOther']}</td></tr>";
        }
        $body .= "</table><br>This message is automatically generated by Bodwell database system.";

        $cc = parseRecipientsFromString($MSPbccEmail, ''); // or 'MSP BCC'
        // print_r($cc);
        $sendEmail = sendEmail(
            ['email' => 'no-reply@bodwell.edu', 'name' => 'Bodwell System'],
            $to,
            $cc,
            $subject,
            $body
        );
        echo $sendEmail;
    }

    // Unverified Parent Emails - Counselor
    $stmt2 = $pdo->prepare("
        SELECT s.StudentID, s.LastName, s.FirstName, s.EnglishName, s.Sex, s.Origin, s.Counselor, s.DOB,
               ct.EmailPersonal AS Email, c.CName, 'Parent/Guardian 1' AS Contact
        FROM tblBHSStudent s
        JOIN tblBHSStudentContact ct ON s.StudentID = ct.StudentID
        JOIN tblCountry c ON s.Origin = c.CID
        WHERE s.CurrentStudent = 'Y'
          AND ct.EVInternal = 0
          AND ct.EmailPersonal <> ''
          AND ct.ContactTab = 'PG1'
          AND s.Counselor = ?
    ");
    $stmt2->execute([$fullname]);
    $rows2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    if (count($rows2) > 0) {
        $body = "<p>Hi, $first,<br><br>The following current student's Parent/Guardian 1 email address has NOT been verified.<br><br>";
        $body .= "<table border='1' cellspacing='0' cellpadding='0' style='font-size:10pt;'><tr><td>Student ID</td><td>Name</td><td>Sex</td><td>DOB</td><td>Origin</td><td>Counselor</td><td>Contact</td><td>Email</td></tr>";
        foreach ($rows2 as $r) {
            $body .= "<tr><td>{$r['StudentID']}</td><td>{$r['LastName']}, {$r['FirstName']} {$r['EnglishName']}</td><td>{$r['Sex']}</td><td>".date('Y-m-d', strtotime($r['DOB']))."</td><td>".substr($r['CName'], 20)."</td><td>{$r['Counselor']}</td><td>{$r['Contact']}</td><td>{$r['Email']}</td></tr>";
        }
        $body .= "</table><br>This message is automatically generated by Bodwell database system.";



        $sendEmail2 = sendEmail(
            ['email' => 'no-reply@bodwell.edu', 'name' => 'Bodwell System'],
            [['email' => $email3, 'name' => $first . ' ' . $last]],
            [],
            "Email address NOT verified Notice",
            $body
        );
        echo $sendEmail2;
    }



}

// Remaining 3 global email sections coming in next message (character limit)...
