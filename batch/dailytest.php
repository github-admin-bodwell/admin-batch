<?php
// Attendance reminder (PHP 5.6 compatible)
date_default_timezone_set('America/Vancouver');

require_once __DIR__ . '/sendEmailClass.php';

// ====== CONFIG ======
$TEST_MODE = true; // set to false for production emails

// ====== DB ======
$dsn = "odbc:Driver={SQL Server};Server=10.100.4.6;Database=Bodwell;Uid=web;Pwd=AJgw!cG4nw;";
$conn = new PDO($dsn);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
// Helps with LIKE and parameter emulation on some ODBC drivers
$conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

// ====== TIME / FLAGS ======
$now = new DateTime('now', new DateTimeZone('America/Vancouver'));
$todayStr = $now->format('Y-m-d');

// CF-style day number: 1=Sun .. 7=Sat  (to match ColdFusion dayofweek())
$cfDay = ((int)$now->format('w')) + 1;

// ISO weekday (1=Mon..7=Sun) for block mapping array below
$weekdayN = (int)$now->format('N');

$isFriday = isset($_GET['isFriday']) ? ($_GET['isFriday'] === 'Y') : false;
$period   = isset($_GET['theperiod']) ? (int)$_GET['theperiod'] : 0;

// ====== Holiday check ======
$holidayFile  = __DIR__ . '/holiday.txt';
$holidayDates = file_exists($holidayFile) ? file($holidayFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : array();
if (in_array($todayStr, $holidayDates, true)) exit;

// ====== Current semester check ======
$semester = $conn->query("SELECT SemesterID, StartDate, EndDate FROM tblBhsSemester WHERE CurrentSemester = 'Y'")
                 ->fetch(PDO::FETCH_ASSOC);
if (!$semester) exit;

$start = new DateTime($semester['StartDate']);
$end   = (new DateTime($semester['EndDate']))->modify('+1 day'); // inclusive end
if ($now < $start || $now > $end) exit;

// ====== CF-equivalent weekday/isFriday gating ======
// CF expression: (Mon..Thu AND NOT Friday-flag) OR (Fri AND Friday-flag)
// CF days: Mon=2..Thu=5, Fri=6
if (!( ($cfDay >= 2 && $cfDay <= 5 && !$isFriday)   // Mon..Thu AND NOT Friday flag
    ||  ($cfDay === 6 &&  $isFriday) )) {           // Fri AND Friday flag
    exit;
}

// ====== Determine block (using ISO weekdayN: 1=Mon..5=Fri) ======
if ($period < 1 || $period > 5) exit; // guard
$blockMapByIso = array(
    1 => array(1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5), // Mon
    2 => array(1 => 5, 2 => 1, 3 => 2, 4 => 3, 5 => 4), // Tue
    3 => array(1 => 4, 2 => 5, 3 => 1, 4 => 2, 5 => 3), // Wed
    4 => array(1 => 3, 2 => 4, 3 => 5, 4 => 1, 5 => 2), // Thu
    5 => array(1 => 2, 2 => 3, 3 => 4, 4 => 5, 5 => 1), // Fri
);
if (!isset($blockMapByIso[$weekdayN][$period])) exit;

$block    = $blockMapByIso[$weekdayN][$period];
// Day index used in legacy CF logic (likely stored in s.SDay); CF Mon..Fri = 2..6
$dayIndex = (int)$now->format('w');

// ====== Load 1038 recipients (BCC pool) ======
$email1038 = '';
$stmt1038 = $conn->prepare("SELECT Email3 FROM tblStaff WHERE GroupTeam LIKE '%1038%' AND CurrentStaff = 'Y'");
$stmt1038->execute();
$emails = $stmt1038->fetchAll(PDO::FETCH_COLUMN);
if ($emails) {
    // Filter empties and join; later we split to build bcc list
    $emails = array_filter($emails, function($e){ return (bool)trim($e); });
    $email1038 = implode(';', $emails);
}

// ====== Fetch subjects that need attendance ======
// Note: tblBhsAttendanceRpt.SDate is TEXT → compare on LEFT(CAST(... AS varchar(50)),10)
$sql = "
SELECT s.SubjectName, s.SubjectID, t.FirstName, t.LastName, t.Email3,
    (SELECT rdate FROM tblbhsAttendanceRpt WHERE SubjectID = s.SubjectID AND LEFT(CAST(SDate AS varchar(50)),10) = '{today}') AS rdate,
    (SELECT rdate FROM tblbhsAttendanceRpt WHERE SubjectID = s.SameBlock AND LEFT(CAST(SDate AS varchar(50)),10) = '{today}') AS rdate2
FROM tblBHSSUBJECT s
JOIN tblStaff t ON s.TeacherID = t.StaffID
WHERE s.SemesterID = '{semester}'
    AND s.Block = '{block}'
    AND s.SDay LIKE '%{dayIndex}%'
    AND s.SubjectName NOT LIKE 'YYY%'
    AND s.Type <> 'C'
    AND s.SPA <> 0
ORDER BY t.FirstName, t.LastName, s.SubjectName
";



$sql = str_replace(
    ['{today}', '{semester}', '{block}', '{dayIndex}'],
    [
        addslashes($todayStr),
        addslashes($semester['SemesterID']),
        addslashes($block),
        addslashes($dayIndex)
    ],
    $sql
);

echo $sql;

$stmt = $conn->prepare($sql);

$stmt->execute($params);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
// ====== Email sending ======
foreach ($subjects as $subj) {
    // If neither rdate nor rdate2 exists for today → no report submitted
    if (empty($subj['rdate']) && empty($subj['rdate2']) && !empty($subj['Email3'])) {

        // Build TO
        $toList = array(
            array('email' => $subj['Email3'], 'name' => $subj['FirstName'] . ' ' . $subj['LastName'])
        );

        // Build CC (always CC Chanho for visibility; adjust as needed)
        $ccList = array(
            array('email' => 'chanho.lee@bodwell.edu', 'name' => 'Chanho Lee')
        );

        // Build BCC from 1038 group
        $bccList = array();
        if ($email1038) {
            $addrs = explode(';', $email1038);
            foreach ($addrs as $addr) {
                $addr = trim($addr);
                if ($addr !== '') {
                    $bccList[] = array('email' => $addr, 'name' => '');
                }
            }
        }

        // In TEST_MODE, override recipients to avoid mass send
        if ($TEST_MODE) {
            $toList  = array(array('email' => 'chanho.lee@bodwell.edu', 'name' => 'Chanho Lee (TEST)'));
            $ccList  = array();
            $bccList = array();
        }

        $subjectLine = "Attendance reminder on " . $todayStr;
        $html = "Dear " . htmlspecialchars($subj['FirstName']) . ",<br><br>" .
                "According to our records, we did not receive an attendance report for " .
                "<b>" . htmlspecialchars($subj['SubjectName']) . "</b> today. " .
                "Please submit the attendance report accordingly.<br><br><br>" .
                "This message is automatically generated by the Bodwell database system.";

        // SEND
        // sendEmail(from, to[], cc[], subject, html, bcc[])
        // Your sendEmail signature per memory: sendEmail($from, $to, $cc, $subject, $body, $altBody='') — but you also use arrays of ['email','name'].
        // Assuming your class supports: from = ['email'=>..., 'name'=>...], to/cc/bcc arrays.
        $from = array('email' => 'no-reply@bodwell.edu', 'name' => 'Attendance Reminder');
        // If your sendEmailClass doesn't accept $bccList as the 6th arg, update accordingly.
        // sendEmail($from, $toList, $ccList, $subjectLine, $html, $bccList);
    }
}

exit;
