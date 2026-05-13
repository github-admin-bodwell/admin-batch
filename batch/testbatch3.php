<?php

require_once __DIR__ . '/sendEmailClass.php';
$dsn = "odbc:Driver={SQL Server};Server=10.100.4.6;Database=Bodwell;Uid=web;Pwd=AJgw!cG4nw;";
$conn = new PDO($dsn);

// Step 1: Skip if today is holiday
$holidayFile = __DIR__ . "/holiday.txt";
$holidayDate = @file_get_contents($holidayFile);
$today = '2025-09-15';
if (!$holidayDate || $today === trim($holidayDate)) exit;

// Step 2: Only run Monday–Friday
$dayOfWeek = date('N'); // 1 = Monday, ..., 7 = Sunday
echo $dayOfWeek;
if ($dayOfWeek < 1 || $dayOfWeek > 5) exit;

// Step 3: Get current semester
$stmt = $conn->query("SELECT SemesterID, StartDate, EndDate FROM tblbhssemester WHERE CurrentSemester = 'Y'");
$sem = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$sem) exit;

$start = $sem['StartDate'];
$end = date('Y-m-d', strtotime($sem['EndDate'] . ' +1 day'));
if ($today < $start || $today > $end) exit;

$semesterid = $sem['SemesterID'];
$thedate = '2025-09-18';

// Step 5: Daily summary for 1038
$stmt = $conn->prepare("SELECT RDate FROM tblbhsAttendanceRpt WHERE SDate = ?");
$stmt->execute([$today]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    $sql = "SELECT sub.SubjectID, sub.SubjectName, st.FirstName, st.LastName
            FROM tblbhsSubject sub
            JOIN tblStaff st ON sub.TeacherID = st.StaffID
            WHERE sub.SubjectName NOT LIKE 'YYY%' AND sub.SubjectName NOT LIKE 'ZZZ%'
              AND sub.SemesterID = ? AND sub.SDay LIKE ? AND sub.Type <> 'C' AND sub.SPA <> 0
            ORDER BY st.LastName, st.FirstName, sub.SubjectName";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$semesterid, '%1%']);
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
    echo $html;
    // Get Email1038 list
    $emails1038 = $conn->query("SELECT Email3 FROM tblStaff WHERE GroupTeam LIKE '%1038%' AND CurrentStaff = 'Y'")->fetchAll(PDO::FETCH_COLUMN);
    // $to = array_map(function($e) {
    //     return ['email' => $e, 'name' => ''];
    // }, $emails1038);
    $to = [['email' => 'chanho.lee@bodwell.edu', 'name' => 'chanholee']];

    $cc = [];

    $from = ['email' => 'no-reply@bodwell.edu', 'name' => 'Attendance Bot'];
    echo sendEmail($from, $to, $cc, "Attendance Daily Submission Report", $html);
}
?>
