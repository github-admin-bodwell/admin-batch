<?php
require_once __DIR__.'/sendEmailClass.php';

$dsn = "odbc:Driver={SQL Server};Server=10.123.1234;Database=bbbb;Uid=web;Pwd=sadasasd;";
$conn = new PDO($dsn);

$today = date('Y-m-d');
$dayOfWeek = date('w'); // PHP: 0=Sun .. 6=Sat
$cfDay = $dayOfWeek === 0 ? 1 : $dayOfWeek + 1; // CF-style dayofweek

// Skip if before cutoff
if ($today <= '2022-01-10') exit;

// Skip if today is a holiday
$holidayDate = trim(@file_get_contents(__DIR__ . '/holiday.txt'));
if ($today === $holidayDate) exit;

// Skip if not Mon–Fri
if ($cfDay < 2 || $cfDay > 6) exit;

// Get current semester
$sql = "SELECT SemesterID, StartDate, EndDate FROM tblbhssemester WHERE CurrentSemester = 'Y'";
$stmt = $conn->query($sql);
$semester = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$semester) exit;

$startDate = $semester['StartDate'];
$endDate = date('Y-m-d', strtotime($semester['EndDate'] . ' +1 day'));
if ($today < $startDate || $today > $endDate) exit;

// =========================
// INDIVIDUAL COUNSELOR REPORTS
// =========================
$counselorQuery = "SELECT FirstName, LastName, Email3 FROM tblstaff
                   WHERE SchoolID IN ('BHS', 'BSS') AND PositionTitle LIKE '%counselor%' AND CurrentStaff = 'Y'
                   ORDER BY FirstName, LastName";
$counselors = $conn->query($counselorQuery)->fetchAll(PDO::FETCH_ASSOC);

foreach ($counselors as $counselor) {
    $fullName = $counselor['FirstName'] . ' ' . $counselor['LastName'];
    $email = $counselor['Email3'];

    $sql = "SELECT ss.StudNum, s.LastName, s.FirstName, s.EnglishName, s.Origin,
                   SUM(a.AbsencePeriod) AS AbsencePeriod,
                   SUM(a.LatePeriod) AS LatePeriod
            FROM tblbhsattendance a
            JOIN tblbhsstudentsubject ss ON ss.StudSubjID = a.StudSubjID
            JOIN tblbhsstudent s ON s.StudentID = ss.StudNum
            WHERE a.ADate = ?
              AND s.Counselor = ?
              AND (a.AbsencePeriod > 0 OR a.LatePeriod > 0)
            GROUP BY s.LastName, s.FirstName, s.EnglishName, ss.StudNum, s.Origin";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$today, $fullName]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($records) === 0) continue;

    $body = "Dear {$counselor['FirstName']},<br><br>Here is the attendance report for {$today}.<br><br>";
    $body .= '<table border="0" cellspacing="0" cellpadding="0" style="font-size:8pt;font-family:Verdana">';
    $body .= '<tr><td height="1" colspan="7"><hr width="100%" size="1"></td></tr>';

    foreach ($records as $rec) {
        $studentHeader = "{$rec['LastName']}, {$rec['FirstName']}";
        if (!empty($rec['EnglishName'])) $studentHeader .= " ({$rec['EnglishName']})";
        $body .= "<tr><td>{$studentHeader}</td><td>&nbsp;&nbsp;{$rec['Origin']}&nbsp;&nbsp;</td>
                  <td>Subject Name/Date</td><td>Excuses</td><td>No Excuse</td><td>Total Absence</td><td>Late Period</td></tr>
                  <tr><td colspan='7'><hr width='100%' size='1'></td></tr>";

        $sql2 = "SELECT a.Excuse, a.AbsencePeriod, a.LatePeriod, sub.SubjectName, a.ADate
                 FROM tblbhsattendance a
                 JOIN tblbhsstudentsubject ss ON ss.StudSubjID = a.StudSubjID
                 JOIN tblbhssubject sub ON sub.SubjectID = ss.SubjectID
                 WHERE ss.StudNum = ? AND a.ADate = ? AND (a.AbsencePeriod > 0 OR a.LatePeriod > 0)
                 ORDER BY a.ADate, sub.SubjectName, a.StudSubjID";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->execute([$rec['StudNum'], $today]);
        $details = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        $var1 = $var2 = $var3 = $var4 = 0;
        foreach ($details as $d) {
            $noExcuse = $d['AbsencePeriod'] - $d['Excuse'];
            $var1 += $d['Excuse'];
            $var2 += $noExcuse;
            $var3 += $d['AbsencePeriod'];
            $var4 += $d['LatePeriod'];

            $body .= "<tr><td colspan='2'>{$d['SubjectName']}</td>
                      <td>" . date('m/d/Y', strtotime($d['ADate'])) . "</td>
                      <td align='right'>{$d['Excuse']}</td>
                      <td align='right'>{$noExcuse}</td>
                      <td align='right'>{$d['AbsencePeriod']}</td>
                      <td align='right'>{$d['LatePeriod']}</td></tr>";
        }

        $body .= "<tr><td colspan='2'>&nbsp;</td><td colspan='5'><hr width='100%' size='1'></td></tr>";
        $body .= "<tr><td colspan='3'>&nbsp;</td>
                  <td align='right'>{$var1}</td>
                  <td align='right'>{$var2}</td>
                  <td align='right'>{$var3}</td>
                  <td align='right'>{$var4}</td></tr>
                  <tr><td>&nbsp;</td></tr>";
    }

    $body .= '</table>';
    sendEmail(
        ['email' => $email, 'name' => 'Attendance System'],
        [['email' => $email, 'name' => $counselor['FirstName']]],
        [],
        "Attendance Report",
        $body,
        ''
    );
}

// =========================
// DAILY SUBMISSION REPORT
// =========================
$sql = "SELECT RDate FROM tblbhsattendancerpt WHERE SDate = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$today]);
if ($stmt->rowCount() > 0) {
    $sql = "SELECT sub.SubjectID, sub.SubjectName, t.FirstName, t.LastName
            FROM tblbhssubject sub
            JOIN tblstaff t ON sub.TeacherID = t.StaffID
            WHERE sub.SemesterID = ?
              AND sub.SubjectName NOT LIKE 'YYY%'
              AND sub.SubjectName NOT LIKE 'ZZZ%'
              AND sub.Type <> 'C' AND sub.SPA <> 0
              AND sub.SDay LIKE ?";
    $cfDay = (int)date('w') == 0 ? 1 : ((int)date('w') + 1);
    $sdayLike = "%" . ($cfDay - 1) . "%";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$semester['SemesterID'], $sdayLike]);
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $body = "Here is the attendance daily submission report for {$today}.<br><br>";
    $body .= '<table border="0" cellspacing="0" cellpadding="0" style="font-size:10pt;">
              <tr><th>Teacher Name</th><th>Class Name</th><th>Attendance Time</th></tr>';

    foreach ($subjects as $s) {
        $stmt2 = $conn->prepare("SELECT RDate FROM tblbhsattendancerpt WHERE SubjectID = ? AND SDate = ?");
        $stmt2->execute([$s['SubjectID'], $today]);
        $rdate = $stmt2->fetchColumn();
        $rtext = $rdate ? date('F d, Y H:i', strtotime($rdate)) : '';
        $body .= "<tr><td>{$s['LastName']}, {$s['FirstName']}</td><td>{$s['SubjectName']}</td><td>{$rtext}</td></tr>";
    }

    $body .= '</table>';

    // FETCH Email1038
    $get1038 = $conn->query("
        SELECT FirstName, LastName, Email3
        FROM tblStaff
        WHERE GroupTeam LIKE '%1038%' AND CurrentStaff = 'Y'
        ORDER BY FirstName, LastName
    ")->fetchAll(PDO::FETCH_ASSOC);

    $toList = [];
    foreach ($get1038 as $row) {
        if (!empty($row['Email3'])) {
            $toList[] = ['email' => $row['Email3'], 'name' => "{$row['FirstName']} {$row['LastName']}"];
        }
    }

    sendEmail(
        ['email' => 'no-reply@bodwell.edu', 'name' => 'Attendance System'],
        $toList,
        [],
        "Attendance Daily Submission Report",
        $body,
        ''
    );
}
?>
