<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
$dsn = "odbc:Driver={SQL Server};Server=10.100.4.6;Database=Bodwell;Uid=web;Pwd=AJgw!cG4nw;";
$pdo = new PDO($dsn);

$dateToday = date('Y-m-d');
$filePath = 'E:/Batch/students.csv';

// Get current semester
$stmt = $pdo->query("SELECT * FROM tblbhssemester WHERE currentsemester = 'Y'");
$semester = $stmt->fetch(PDO::FETCH_ASSOC);

// Get current students
$query = "SELECT s.studentid,
       CASE WHEN NULLIF(s.englishname, '') IS NULL
            THEN s.firstname + ' ' + s.lastname
            ELSE s.firstname + ' ' + s.lastname + ' (' + s.englishname + ')'
       END AS fullname,
       CONVERT(VARCHAR(10), s.dob, 120) AS dob,
       s.photo, s.houses, s.schoolemail, s.currentgrade,
       h.fobid, h.roomno, h.roomno2, h.halls,
       h.hadvisor + '/' + h.hadvisor2 AS hadvisor,
       h.residence
FROM tblbhsstudent s
JOIN tblbhshomestay h ON s.studentid = h.studentid
WHERE s.currentstudent = 'Y'";

$students = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
$fileLines = [];
$counter = 1;

foreach ($students as $student) {
    $studentID = $student['studentid'];
    $dob = $student['dob'];
    $thevalue = '';
    // AWP Confirmed
    $awpStmt = $pdo->query("SELECT TOP 1 AWPID FROM tblbhsawp WHERE studentid = '$studentID' AND adate1 = '$dateToday' AND acr = 'C'");
    $awpRow = $awpStmt->fetch(PDO::FETCH_ASSOC);
    $awpConfirmed = $awpRow ? 1 : 0;

    // Participation Hours
    $semID = $semester['SemesterID'];
    $startDate = $semester['StartDate'];
    $endDate = date('Y-m-d', strtotime($semester['NextStartDate'] . ' -1 day'));
    $ttlStmt = $pdo->query("SELECT SUM(hours) AS ttlhours FROM tblbhsspstudentactivities WHERE studentid = '$studentID' AND semesterid = '$semID' AND activitystatus = '80' AND sdate >= '$startDate' AND sdate <= '$endDate'");
    $ttl = $ttlStmt->fetch(PDO::FETCH_ASSOC);
    $ttlHours = $ttl['ttlhours'] ? number_format($ttl['ttlhours'], 1) : '0.0';

    // Tidiness
    $tidyStmt = $pdo->query("SELECT tidy1 FROM tblbhsdormdailylog WHERE studentid = '$studentID' AND rdate = '$dateToday'");
    $tidyValue = 2;
    if ($row = $tidyStmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['tidy1'] >= 1 && $row['tidy1'] <= 3) $tidyValue = 1;
        elseif ($row['tidy1'] == 5) $tidyValue = 0;
    }

    // Age Value
    if (!empty($dob)) {
        $age = date('Y') - date('Y', strtotime($dob));
        $thevalue = ($age <= 14) ? 1 : (($age <= 16) ? 2 : 3);
    }

    $line = $counter++ . ',' .
        $studentID . ',' .
        $student['fobid'] . ',' .
        $student['fullname'] . ',' .
        $dob . ',' .
        'https://admin.bodwell.edu/bhs/studentimages/' . $student['photo'] . ',' .
        $student['roomno'] . $student['roomno2'] . ',' .
        $student['halls'] . ',' .
        $student['hadvisor'] . ',' .
        $student['schoolemail'] . ',' .
        $awpConfirmed . ',' .
        $thevalue . ',' .
        $student['currentgrade'] . ',' .
        $student['residence'] . ',' .
        $ttlHours . ',' .
        $student['houses'] . ',' .
        $tidyValue;

    $fileLines[] = $line;
}

$result = file_put_contents($filePath, implode("\r\n", $fileLines));

if ($result === false) {
    die("Failed to write the CSV file to $filePath");
} else {
    echo "CSV file created successfully at $filePath";
}
