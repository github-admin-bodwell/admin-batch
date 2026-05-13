<?php
require_once __DIR__.'/sendEmailClass.php';

$dsn = "odbc:Driver={SQL Server};Server=10.100.4.6;Database=Bodwell;Uid=web;Pwd=AJgw!cG4nw;";
$conn = new PDO($dsn);

$today = date('Y-m-d');
$plus1 = date('Y-m-d', strtotime('+1 day'));
$plus2 = date('Y-m-d', strtotime('+2 day'));
$plus7 = date('Y-m-d', strtotime('+7 day'));

function sendHtmlEmail($from, $to, $cc, $subject, $html, $altBody = '') {
    sendEmail($from, $to, $cc, $subject, $html, $altBody);
}

function buildDetailTable($conn, $eventid, $subjectid) {
    $sql = "SELECT d.*, s.FirstName, s.LastName, s.EnglishName, sub.SubjectName, ss.sstatus, sem.SemesterName,
                   h.Residence, h.Homestay
            FROM tblbhsactivitiespaymentdetail d
            JOIN tblbhsstudent s ON s.StudentID = d.StudentID
            JOIN tblbhsstudentinfo si ON si.StudentID = s.StudentID
            JOIN tblbhsstudentsubject ss ON ss.StudNum = s.StudentID
            JOIN tblbhssubject sub ON sub.SubjectID = ss.SubjectID
            JOIN tblbhssemester sem ON sem.SemesterID = sub.SemesterID
            JOIN tblbhshomestay h ON h.StudentID = s.StudentID
            WHERE d.EventID = :eventid AND sub.SubjectID = :subjectid
            ORDER BY s.LastName, s.FirstName";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':eventid' => $eventid, ':subjectid' => $subjectid]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $html = '<table border="1" cellspacing="0" cellpadding="0" width="900" style="font-size:8pt;font-family:verdana,arial,sans-serif">';
    $html .= '
        <tr><td colspan="4" rowspan="2" align="center">(L) Left School, (W) Withdrawn</td>
        <td height="24" colspan="8" align="center" style="font-weight:bold;">Office Use Only</td></tr>
        <tr align="center" style="font-weight:bold;"><td colspan="2">&nbsp;</td>
        <td height="24" colspan="5">Payment Method</td><td>&nbsp;</td></tr>
        <tr style="font-weight:bold;" align="center">
        <td>#</td><td width="75">Student No.</td><td>Student Name</td><td width="50">Living in</td>
        <td width="90">Amount<br>to be paid</td><td width="90">Date Paid</td>
        <td width="50">Cash</td><td width="50">Debit</td><td width="50">Credit</td>
        <td width="50">Cheque</td><td width="50">W/T</td><td width="90">Notes</td></tr>';

    $i = 1;
    foreach ($rows as $row) {
        $studentName = ($row['sstatus'] ? "({$row['sstatus']}) " : '') . strtoupper($row['LastName']) . ", {$row['FirstName']}";
        if (!empty($row['EnglishName'])) {
            $studentName .= " ({$row['EnglishName']})";
        }
        $living = $row['Residence'] == 'Y' ? 'Boarding' : ($row['Homestay'] == 'Y' ? 'Homestay' : 'Day');

        $html .= "<tr>";
        $html .= "<td>{$i}.</td>";
        $html .= "<td>{$row['StudentID']}</td>";
        $html .= "<td>{$studentName}</td>";
        $html .= "<td align='center'>{$living}</td>";
        $html .= "<td align='right'>$" . number_format($row['Amounttobepaid'], 2) . "</td>";
        $html .= "<td align='center'>{$row['DatePaid']}</td>";

        foreach (['CASH', 'DEBIT', 'CREDIT', 'CHEQUE', 'W/T'] as $method) {
            $html .= "<td align='center'>" . ($row['PaymentMethod'] === $method ? number_format($row['AmountPaid'], 2) : '') . "</td>";
        }

        $html .= "<td>{$row['Notes']}</td>";
        $html .= "</tr>";
        $i++;
    }

    $html .= '</table>';
    return $html;
}

function fetchAndSendEventEmails($conn, $label, $targetDate) {
    $sql = "SELECT ap.*, s.SemesterName, t.FirstName, t.LastName, t.Email3, sub.SubjectName
            FROM tblbhsactivitiespayment ap
            JOIN tblbhssubject sub ON sub.SubjectID = ap.SubjectID
            JOIN tblbhssemester s ON s.SemesterID = sub.SemesterID
            JOIN tblstaff t ON t.StaffID = ap.TeacherInCharge
            WHERE ap.Deadline = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$targetDate]);

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $maintbl = buildDetailTable($conn, $row['EventID'], $row['SubjectID']);
        $from = ['email' => $row['Email3'], 'name' => 'Activities Office'];
        $to = [
            ['email' => $row['Email3'], 'name' => "{$row['FirstName']} {$row['LastName']}"],
            ['email' => 'carolynne.robertson@bodwell.edu', 'name' => 'Carolynne']
        ];
        $cc = [];
        $subject = "Student Activities Payment ({$label})";
        sendHtmlEmail($from, $to, $cc, $subject, $maintbl);
    }
}

fetchAndSendEventEmails($conn, '1 week before deadline', $plus7);
fetchAndSendEventEmails($conn, '2 days before deadline', $plus2);
fetchAndSendEventEmails($conn, 'today deadline', $today);

// Reminder: 1 day before
$sql = "SELECT s.FirstName, s.LastName, s.EnglishName, s.SchoolEmail, ap.EventName, ap.Deadline,
               t.FirstName as FName, t.LastName as LName, t.Email3, apd.Amounttobepaid
        FROM tblbhsstudent s
        JOIN tblbhsactivitiespaymentdetail apd ON apd.StudentID = s.StudentID
        JOIN tblbhsactivitiespayment ap ON ap.EventID = apd.EventID
        JOIN tblstaff t ON t.StaffID = ap.TeacherInCharge
        WHERE ap.Deadline = ? AND apd.Amounttobepaid <> 0 AND apd.AmountPaid IS NULL AND s.SchoolEmail <> ''";
$stmt = $conn->prepare($sql);
$stmt->execute([$plus1]);

foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $from = ['email' => $row['Email3'], 'name' => "{$row['FName']} {$row['LName']}"];
    $to = [['email' => $row['SchoolEmail'], 'name' => "{$row['FirstName']} {$row['LastName']}"]];
    $subject = "{$row['EventName']} (due tomorrow)";
    $body = "Dear " . strtoupper($row['LastName']) . ", {$row['FirstName']},<br><br>" .
            "Please pay $" . number_format($row['Amounttobepaid'], 2) . " for the {$row['EventName']} at front desk by {$row['Deadline']}." .
            "<br><br>Best regards,<br><br>{$row['FName']} {$row['LName']}";
    sendHtmlEmail($from, $to, [], $subject, $body);
}

// Reminder: today
$stmt->execute([$today]);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $from = ['email' => $row['Email3'], 'name' => "{$row['FName']} {$row['LName']}"];
    $to = [['email' => $row['SchoolEmail'], 'name' => "{$row['FirstName']} {$row['LastName']}"]];
    $subject = "{$row['EventName']} (due today)";
    $body = "Dear " . strtoupper($row['LastName']) . ", {$row['FirstName']},<br><br>" .
            "Please pay $" . number_format($row['Amounttobepaid'], 2) . " for the {$row['EventName']} at front desk today, {$row['Deadline']}." .
            "<br><br>Best regards,<br><br>{$row['FName']} {$row['LName']}";
    sendHtmlEmail($from, $to, [], $subject, $body);
}

// Reminder: past due
$sql = "SELECT s.FirstName, s.LastName, s.EnglishName, s.SchoolEmail, ap.EventName, ap.Deadline,
               t.FirstName as FName, t.LastName as LName, t.Email3, apd.Amounttobepaid
        FROM tblbhsstudent s
        JOIN tblbhsactivitiespaymentdetail apd ON apd.StudentID = s.StudentID
        JOIN tblbhsactivitiespayment ap ON ap.EventID = apd.EventID
        JOIN tblstaff t ON t.StaffID = ap.TeacherInCharge
        WHERE ap.Deadline >= '2019-09-01' AND ap.Deadline < ?
              AND apd.Amounttobepaid <> 0 AND apd.AmountPaid IS NULL AND s.SchoolEmail <> ''
        ORDER BY ap.Deadline DESC";
$stmt = $conn->prepare($sql);
$stmt->execute([$today]);

foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $from = ['email' => 'no-reply@bodwell.edu', 'name' => 'Bodwell'];
    $to = [['email' => $row['SchoolEmail'], 'name' => "{$row['FirstName']} {$row['LastName']}"]];
    $cc = [['email' => $row['Email3'], 'name' => "{$row['FName']} {$row['LName']}"]];
    $subject = "{$row['EventName']} (due already)";
    $body = "Dear " . strtoupper($row['LastName']) . ", {$row['FirstName']},<br><br>" .
            "Please pay $" . number_format($row['Amounttobepaid'], 2) . " for the {$row['EventName']} at front desk. Deadline was {$row['Deadline']}." .
            "<br><br>Best regards,<br><br>{$row['FName']} {$row['LName']}";
    sendHtmlEmail($from, $to, $cc, $subject, $body);
}
?>
