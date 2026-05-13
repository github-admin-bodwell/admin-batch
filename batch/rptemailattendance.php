<?php

require_once __DIR__ . '/sendEmailClass.php';
$dsn = "odbc:Driver={SQL Server};Server=10.100.4.6;Database=Bodwell;Uid=web;Pwd=AJgw!cG4nw;";
$conn = new PDO($dsn);

// Step 1: Skip if today is holiday
$holidayFile = __DIR__ . "/holiday.txt";
$holidayDate = @file_get_contents($holidayFile);
$today = date('Y-m-d');
if (!$holidayDate || $today === trim($holidayDate)) exit;

// Step 2: Only run Monday–Friday
$dayOfWeek = date('N'); // 1 = Monday, ..., 7 = Sunday
if ($dayOfWeek < 1 || $dayOfWeek > 5) exit;

// Step 3: Get current semester
$stmt = $conn->query("SELECT SemesterID, StartDate, EndDate FROM tblbhssemester WHERE CurrentSemester = 'Y'");
$sem = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$sem) exit;

$start = $sem['StartDate'];
$end = date('Y-m-d', strtotime($sem['EndDate'] . ' +1 day'));
if ($today < $start || $today > $end) exit;

$semesterid = $sem['SemesterID'];
$thedate = date('m/d/Y');

// Step 4: Attendance report per counselor
$counselors = $conn->query("SELECT FirstName, LastName, Email3 FROM tblStaff WHERE SchoolID IN ('BHS','BSS') AND PositionTitle LIKE '%counselor%' AND CurrentStaff = 'Y' ORDER BY FirstName, LastName")->fetchAll(PDO::FETCH_ASSOC);

foreach ($counselors as $c) {
    $fullname = $c['FirstName'] . ' ' . $c['LastName'];

    $sql = "SELECT SUM(a.AbsencePeriod) AS AbsencePeriod, SUM(a.LatePeriod) AS LatePeriod,
                   ss.StudNum, s.LastName, s.FirstName, s.EnglishName, s.Origin
            FROM tblbhsAttendance a
            JOIN tblbhsStudentSubject ss ON ss.StudSubjID = a.StudSubjID
            JOIN tblbhsStudent s ON s.StudentID = ss.StudNum
            WHERE a.ADate = ? AND s.Counselor = ? AND (a.AbsencePeriod > 0 OR a.LatePeriod > 0)
            GROUP BY s.LastName, s.FirstName, s.EnglishName, ss.StudNum, s.Origin";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$today, $fullname]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$students) continue;

    $html = "Dear {$c['FirstName']},<br><br>Here is the attendance report for {$thedate}.<br><br><table border='0' cellspacing='0' cellpadding='0' style='font-size:8pt;font-family:Verdana'>";
    foreach ($students as $s) {
        $studentName = "{$s['LastName']}, {$s['FirstName']}";
        if (!empty($s['EnglishName'])) $studentName .= " ({$s['EnglishName']})";

        $html .= "<tr><td>{$studentName}</td><td>&nbsp;&nbsp;{$s['Origin']}&nbsp;&nbsp;</td><td>Subject Name/Date&nbsp;&nbsp;</td><td>Excuses</td><td>&nbsp;&nbsp;No Excuse</td><td>&nbsp;&nbsp;Total Absence</td><td>&nbsp;&nbsp;Late Period</td></tr><tr><td height='1' colspan='7'><hr width='100%' size='1'></td></tr>";

        $sql2 = "SELECT a.Excuse, a.AbsencePeriod, a.LatePeriod, sub.SubjectName, a.ADate
                 FROM tblbhsAttendance a
                 JOIN tblbhsStudentSubject ss ON ss.StudSubjID = a.StudSubjID
                 JOIN tblbhsSubject sub ON sub.SubjectID = ss.SubjectID
                 WHERE ss.StudNum = ? AND a.ADate = ? AND (a.AbsencePeriod > 0 OR a.LatePeriod > 0)
                 ORDER BY a.ADate, sub.SubjectName";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->execute([$s['StudNum'], $today]);
        $absences = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        $var1 = $var2 = $var3 = $var4 = 0;
        foreach ($absences as $a) {
            $noExcuse = $a['AbsencePeriod'] - $a['Excuse'];
            $var1 += $a['Excuse'];
            $var2 += $noExcuse;
            $var3 += $a['AbsencePeriod'];
            $var4 += $a['LatePeriod'];
            $html .= "<tr><td colspan='2'>{$a['SubjectName']}</td><td>" . date('m/d/Y', strtotime($a['ADate'])) . "</td><td align='right'>{$a['Excuse']}</td><td align='right'>{$noExcuse}</td><td align='right'>{$a['AbsencePeriod']}</td><td align='right'>{$a['LatePeriod']}</td></tr>";
        }

        $html .= "<tr><td colspan='2'>&nbsp;</td><td height='1' colspan='5'><hr width='100%' size='1'></td></tr>
                  <tr><td colspan='3'>&nbsp;</td><td align='right'>{$var1}</td><td align='right'>{$var2}</td><td align='right'>{$var3}</td><td align='right'>{$var4}</td></tr>
                  <tr><td>&nbsp;</td></tr>";
    }
    $html .= "</table>";

    $from = ['email' => $c['Email3'], 'name' => $c['FirstName'] . ' ' . $c['LastName']];
    $cc = [];
    $to = [['email' => $c['Email3'], 'name' => $c['FirstName'] . ' ' . $c['LastName']]];

    sendEmail($from, $to, $cc, "Attendance Report", $html);
}

// Step 5: Daily summary for 1038
$stmt = $conn->prepare("SELECT RDate FROM tblbhsAttendanceRpt WHERE SDate = ?");
$stmt->execute([$today]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    $sql = "SELECT sub.SubjectID, sub.SubjectName, st.FirstName, st.LastName
            FROM tblbhsSubject sub
            JOIN tblStaff st ON sub.TeacherID = st.StaffID
            WHERE sub.SubjectName NOT LIKE 'YYY%'
              AND sub.SemesterID = ? AND sub.SDay LIKE ? AND sub.Type <> 'C' AND sub.SPA <> 0
            ORDER BY st.LastName, st.FirstName, sub.SubjectName";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$semesterid, '%' . $dayOfWeek . '%']);
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $html = "Here is the attendance daily submission report for {$thedate}.<br><br><table border='0' cellspacing='0' cellpadding='0' style='font-size:10pt;'><tr><th>Teacher Name</th><th>Class Name</th><th>Attendance Time</th></tr>";

    foreach ($subjects as $s) {
        $stmt = $conn->prepare("SELECT RDate FROM tblbhsAttendanceRpt WHERE SubjectID = ? AND SDate = ?");
        $stmt->execute([$s['SubjectID'], $today]);
        $rdate = $stmt->fetchColumn();
        $timeStr = $rdate ? date('F d, Y H:i', strtotime($rdate)) : '';
        $html .= "<tr><td>{$s['LastName']}, {$s['FirstName']}</td><td>{$s['SubjectName']}</td><td>{$timeStr}</td></tr>";
    }
    $html .= "</table>";

    // Get Email1038 list
    $emails1038 = $conn->query("SELECT Email3 FROM tblStaff WHERE GroupTeam LIKE '%1038%' AND CurrentStaff = 'Y'")->fetchAll(PDO::FETCH_COLUMN);
    $to = array_map(function($e) {
        return ['email' => $e, 'name' => ''];
    }, $emails1038);
    $cc = [];

    $from = ['email' => 'no-reply@bodwell.edu', 'name' => 'Attendance Bot'];
    echo sendEmail($from, $to, $cc, "Attendance Daily Submission Report", $html);
}
?>
