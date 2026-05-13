<?php
require_once __DIR__.'/sendEmailClass.php';

$dsn = "odbc:Driver={SQL Server};Server=10.100.4.6;Database=Bodwell;Uid=web;Pwd=AJgw!cG4nw;";
$conn = new PDO($dsn);

$testDate = '2025-01-10'; // hardcoded test deadline

function sendHtmlEmail($from, $to, $cc, $subject, $html, $altBody = '') {
    sendEmail($from, $to, $cc, $subject, $html, $altBody);
}

function buildDetailTable($conn, $eventid, $subjectid) {
    $sql = "SELECT d.*, s.firstname, s.lastname, s.englishname, sub.subjectname, ss.sstatus, sem.semestername,
                   h.residence, h.homestay
            FROM tblbhsactivitiespaymentdetail d
            JOIN tblbhsstudent s ON s.studentid = d.studentid
            JOIN tblbhsstudentinfo si ON si.studentid = s.studentid
            JOIN tblbhsstudentsubject ss ON ss.studnum = s.studentid
            JOIN tblbhssubject sub ON sub.subjectid = ss.subjectid
            JOIN tblbhssemester sem ON sem.semesterid = sub.semesterid
            JOIN tblbhshomestay h ON h.studentid = s.studentid
            WHERE d.eventid = :eventid AND sub.subjectid = :subjectid
            ORDER BY s.lastname, s.firstname";
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
        $studentName = ($row['sstatus'] ? "({$row['sstatus']}) " : '') . strtoupper($row['lastname']) . ", {$row['firstname']}";
        if (!empty($row['englishname'])) {
            $studentName .= " ({$row['englishname']})";
        }
        $living = $row['residence'] == 'Y' ? 'Boarding' : ($row['homestay'] == 'Y' ? 'Homestay' : 'Day');

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

// Send "today deadline" test email
$sql = "SELECT ap.*, s.semestername, t.firstname, t.lastname, t.email3, sub.subjectname
        FROM tblbhsactivitiespayment ap
        JOIN tblbhssubject sub ON sub.subjectid = ap.subjectid
        JOIN tblbhssemester s ON s.semesterid = sub.semesterid
        JOIN tblstaff t ON t.staffid = ap.teacherincharge
        WHERE ap.deadline = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$testDate]);

foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $maintbl = buildDetailTable($conn, $row['EventID'], $row['SubjectID']);

    $from = ['email' => 'helpdesk@bodwell.edu', 'name' => 'IT Helpdesk'];
    $to = [['email' => 'chanho.lee@bodwell.edu', 'name' => "{$row['firstname']} {$row['lastname']}"]];
    $cc = [['email' => 'kwyes2@hotmail.com', 'name' => 'K.W.']];
    $subject = "Student Activities Payment (TEST — 2025-05-07)";
    sendHtmlEmail($from, $to, $cc, $subject, $maintbl);
}
?>
