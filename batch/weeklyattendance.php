<?php
require_once __DIR__.'/sendEmailClass.php';

$dsn = "odbc:Driver={SQL Server};Server=10.100.4.6;Database=Bodwell;Uid=web;Pwd=AJgw!cG4nw;";
$conn = new PDO($dsn);

$holidayFile = "E:/admin.bodwell.edu/bhs/schedule/holiday.txt";

$today = date('Y-m-d');
$thisdate = date('Y-m-d', strtotime('-6 days'));



$holidays = array();
if (file_exists($holidayFile)) {
    $holidays = file($holidayFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
}

// Current semester
$stmt = $conn->query("SELECT SemesterID, StartDate, EndDate FROM tblbhssemester WHERE CurrentSemester = 'Y'");
$semester = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$semester) {
    exit;
}

$semesterID = $semester['SemesterID'];
$startDate = date('Y-m-d', strtotime($semester['StartDate']));
$endDate = date('Y-m-d', strtotime($semester['EndDate'] . ' +1 day'));

if ($today < $startDate || $today > $endDate) {
    exit;
}


$allRows = array();


// Monday to Friday from last week
for ($i = 0; $i <= 4; $i++) {
    $checkDate = date('Y-m-d', strtotime($thisdate . " +{$i} days"));

    if (in_array($checkDate, $holidays)) {
        continue;
    }

    // PHP: Sunday 0, Monday 1...
    // CF dayofweek - 1 gives Sunday 0, Monday 1...
    $sday = date('w', strtotime($checkDate));

    $sql = "
        SELECT d.LastName + ', ' + d.FirstName AS name,
               a.SubjectName,
               CASE a.Block
                    WHEN '0' THEN '0'
                    WHEN '1' THEN 'A'
                    WHEN '2' THEN 'B'
                    WHEN '3' THEN 'C'
                    WHEN '4' THEN 'D'
                    WHEN '5' THEN 'E'
                    WHEN '6' THEN 'S1'
                    WHEN '7' THEN 'G'
                    WHEN '8' THEN 'S2'
                    WHEN '9' THEN 'S1+S2'
                    WHEN '10' THEN 'G1'
                    WHEN '11' THEN 'G2'
               END AS Block,
               ? AS Sdate,
               a.SameGroup,
               d.Email3
        FROM tblBHSSubject a
        INNER JOIN tblStaff d ON a.TeacherID = d.StaffID
        WHERE a.SemesterID = ?
          AND a.Type <> 'C'
          AND a.SDay LIKE ?
          AND d.Email3 <> ''
          AND (
                SELECT TOP 1 b.SDate
                FROM tblBHSAttendanceRpt b
                WHERE b.SDate = ?
                  AND b.SubjectID = a.SubjectID
              ) IS NULL
          AND (
                -- Attendance group: another section with the same tblBHSSubject.SameGroup
                -- recorded attendance that day (same rule as dailyattendance.php).
                -- SameGroup is maintained on phpadmin's Attendance Groups page; NULL = stands alone.
                -- Was SameBlock, which nothing populates. Changed 2026-09-22.
                SELECT TOP 1 c.SDate
                FROM tblBHSAttendanceRpt c
                INNER JOIN tblBHSSubject e ON c.SubjectID = e.SubjectID
                WHERE c.SDate = ?
                  AND a.SameGroup IS NOT NULL
                  AND a.SameGroup <> ''
                  AND e.SemesterID = a.SemesterID
                  AND e.SameGroup = a.SameGroup
              ) IS NULL
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute(array(
        $checkDate,
        $semesterID,
        '%' . $sday . '%',
        $checkDate,
        $checkDate
    ));

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($rows);
    foreach ($rows as $row) {
        $allRows[] = $row;
    }
}

if (count($allRows) == 0) {
    exit;
}

// Sort like CF query: name, sdate, block, subjectname
usort($allRows, function($a, $b) {
    $x = strcmp($a['name'], $b['name']);
    if ($x != 0) return $x;

    $x = strcmp($a['Sdate'], $b['Sdate']);
    if ($x != 0) return $x;

    $x = strcmp($a['Block'], $b['Block']);
    if ($x != 0) return $x;

    return strcmp($a['SubjectName'], $b['SubjectName']);
});

// Group by teacher name/email
$grouped = array();

foreach ($allRows as $row) {
    $key = $row['Email3'];

    if (!isset($grouped[$key])) {
        $grouped[$key] = array(
            'name' => $row['name'],
            'email' => $row['Email3'],
            'rows' => array()
        );
    }

    $grouped[$key]['rows'][] = $row;
}

foreach ($grouped as $teacher) {
    if ($teacher['email'] == '') {
        continue;
    }

    $emailcontent = "Hi " . $teacher['name'] . ",<br><br>";
    $emailcontent .= "This is a summary of the dates and times where class attendance was not recorded this past week. Accurate and timely attendance taking is a critical responsibility, and we are asking for your full cooperation in ensuring this is done consistently moving forward.<br><br>";
    $emailcontent .= "Incomplete attendance records create significant challenges for our VP, AP, and counselling teams when following up on student absences and can lead to confusion for families and errors in our reporting systems.<br><br>";
    $emailcontent .= "Please treat this as a high-priority task in your daily routine. Your support is essential in maintaining the integrity of our student monitoring process.<br><br>";

    $emailcontent .= '<table border="1" cellpadding="0" cellspacing="0">';

    $ttlnum = 0;
    $lastDate = '';
    $lastBlock = '';

    foreach ($teacher['rows'] as $r) {
        $emailcontent .= '<tr>';
        $emailcontent .= '<td>' . $r['Sdate'] . '</td>';
        $emailcontent .= '<td>Block ' . $r['Block'] . '</td>';
        $emailcontent .= '<td>' . $r['SubjectName'] . '</td>';
        $emailcontent .= '</tr>';

        if ($lastDate != $r['Sdate'] || $lastBlock != $r['Block']) {
            $ttlnum++;
        }

        $lastDate = $r['Sdate'];
        $lastBlock = $r['Block'];
    }

    $emailcontent .= '<tr><td colspan="3">Total: ' . $ttlnum . '</td></tr>';
    $emailcontent .= '</table><br>';

    $emailcontent .= "Thank you for your attention to this matter.<br><br>";
    $emailcontent .= "Housam<br><br>";

    $subject = 'Missed attendance for week of ' . date('F d', strtotime($thisdate));

    $insert = $conn->prepare("
        INSERT INTO tblsendemail
        (sendto, sendfrom, sendsubject, sendattach, sendmessage, senddate, schoolid, sendcc, sendbcc)
        VALUES (?, ?, ?, '', ?, GETDATE(), 'WMA', ?, '')
    ");

    // Production recipients: the teacher, cc Housam / Angela / Shane. This matches what
    // the live task has been sending (verified in tblSendEmail, Sep 2026); the repo copy
    // previously still had the Chanho-only test insert.
    $insert->execute(array(
        $teacher['email'],
        'no-reply@bodwell.edu',
        $subject,
        $emailcontent,
        'hallis@bodwell.edu;angela.jay@bodwell.edu;shane.chaffey@bodwell.edu'
    ));

}
?>
