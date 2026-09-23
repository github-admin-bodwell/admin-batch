<?php
require_once __DIR__ . '/sendEmailClass.php';

$dsn = "odbc:Driver={SQL Server};Server=10.100.4.6;Database=Bodwell;Uid=web;Pwd=AJgw!cG4nw;";
$pdo = new PDO($dsn);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$thedate = date('Y-m-d', strtotime('-1 day'));

// $thedate = '2026-04-30';

$from = ['email' => 'no-reply@bodwell.edu', 'name' => 'Study Hall'];

// =======================
// Helper functions
// =======================

function h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function getOrdinal($number)
{
    $number = (int)$number;

    if ($number === 1) return '1st';
    if ($number === 2) return '2nd';
    if ($number === 3) return '3rd';

    return $number . 'th';
}

function normalizeRecipients($emails)
{
    if (!is_array($emails)) {
        $emails = preg_split('/[;,]/', (string)$emails);
    }

    $recipients = [];

    foreach ($emails as $email) {
        $email = trim((string)$email);

        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $recipients[strtolower($email)] = [
                'email' => $email,
                'name'  => 'Recipient'
            ];
        }
    }

    return array_values($recipients);
}

function getEmailListByGroupTeam(PDO $pdo, $groupTeam)
{
    $stmt = $pdo->prepare("
        SELECT Email3
        FROM tblStaff
        WHERE GroupTeam LIKE ?
          AND CurrentStaff = 'Y'
          AND Email3 <> ''
        ORDER BY FirstName, LastName
    ");

    $stmt->execute(['%' . $groupTeam . '%']);

    $emails = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $email = trim((string)$row['Email3']);

        if ($email !== '') {
            $emails[] = $email;
        }
    }

    return $emails;
}

function getStaffEmailByFullName(PDO $pdo, $fullName)
{
    $fullName = trim((string)$fullName);

    if ($fullName === '') {
        return '';
    }

    $stmt = $pdo->prepare("
        SELECT TOP 1 Email3
        FROM tblStaff
        WHERE SchoolID IN ('BHS','BSS')
          AND CurrentStaff = 'Y'
          AND Department IN ('5','6')
          AND Email3 <> ''
          AND FirstName + ' ' + LastName = ?
    ");

    $stmt->execute([$fullName]);

    return trim((string)$stmt->fetchColumn());
}

function getAbsenceCount(PDO $pdo, $studentID, $semesterStart, $semesterEnd)
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM tblBHSDormDailyLog
        WHERE StudentID = ?
          AND StudyGroup = 'S'
          AND RDate >= ?
          AND RDate <= ?
          AND RStudy = 1
          AND (SBlock IS NULL OR SBlock <> '6:00')
    ");

    $stmt->execute([$studentID, $semesterStart, $semesterEnd]);

    return (int)$stmt->fetchColumn();
}

function getStatusText($rstudy)
{
    if ((int)$rstudy === 1) return 'Unexcused Absent';
    if ((int)$rstudy === 5) return 'Present';
    if ((int)$rstudy === 0) return 'Excused Absent';
    if ((int)$rstudy === 2) return 'Late 5 mins';

    return '';
}

function getAssessmentText($rmeet)
{
    if ((int)$rmeet === 0) return 'N/A';
    if ((int)$rmeet === 1) return 'Needs improvement';
    if ((int)$rmeet === 5) return 'Meets expectations';

    return '';
}

function buildReportRow(PDO $pdo, $r, $semesterStart, $semesterEnd)
{
    $absenceLabel = '&nbsp;';

    if ((int)$r['RStudy'] === 1) {
        $absenceCount = getAbsenceCount($pdo, $r['StudentID'], $semesterStart, $semesterEnd);
        $absenceLabel = getOrdinal($absenceCount);
    }

    $studentName = h($r['LastName']) . ', ' . h($r['FirstName']);

    if (!empty($r['EnglishName'])) {
        $studentName .= ' (' . h($r['EnglishName']) . ')';
    }

    return "
        <tr>
            <td>{$studentName}</td>
            <td>" . h($r['CurrentGrade']) . "</td>
            <td>" . h($r['Counselor']) . "</td>
            <td>" . h($r['HAdvisor']) . "/" . h($r['HAdvisor2']) . "</td>
            <td>" . h($r['Tutor']) . "</td>
            <td align='center'>" . h(getStatusText($r['RStudy'])) . "</td>
            <td align='center'>{$absenceLabel}</td>
            <td>" . h(getAssessmentText($r['RMeet'])) . "</td>
            <td>" . h($r['RComment']) . "&nbsp;</td>
            <td>" . h($r['HAComment']) . "&nbsp;</td>
        </tr>";
}

function sendReport($from, $to, $subject, $rows, $intro)
{
    if (empty($rows)) {
        return;
    }

    $recipients = normalizeRecipients($to);

    if (empty($recipients)) {
        return;
    }

    $recipients = [
        ['email' => 'chanho.lee@bodwell.edu', 'name' => 'Chanho Lee']
    ];

    $html = "
        {$intro}<br><br>

        <table border='1' cellspacing='0' cellpadding='3' style='font-size:8pt;font-family:Verdana' bordercolor='#000000'>
            <tr>
                <td>Student</td>
                <td>Current Grade</td>
                <td>Counselor</td>
                <td>Youth Advisor</td>
                <td>Tutor</td>
                <td>PRESENT/ABSENT</td>
                <td>## OF ABSENCES</td>
                <td>TUTOR ASSESSMENT</td>
                <td width='250'>Tutor comment</td>
                <td width='250'>Advisor comment</td>
            </tr>
            " . implode("\n", $rows) . "
        </table>

        <br>This report is automatically generated by STUDY HALL report system.
    ";

    sendEmail(
        $from,
        $recipients,
        [],
        $subject,
        $html
    );
}

// =======================
// Get current semester
// =======================

$semester = $pdo->query("
    SELECT TOP 1 *
    FROM tblBHSSemester
    WHERE CurrentSemester = 'Y'
")->fetch(PDO::FETCH_ASSOC);

if (!$semester) {
    exit('No current semester found.');
}

$semesterStart = $semester['StartDate'];
$semesterEnd   = $semester['EndDate'];

// =======================
// Main data query
// =======================

$sql = "
SELECT
    s.StudentID,
    s.FirstName,
    s.LastName,
    s.EnglishName,
    s.Counselor,
    s.CurrentGrade,
    h.HAdvisor,
    h.HAdvisor2,
    h.Tutor,
    h.SundayGroup,
    d.RComment,
    d.HAComment,
    d.RStudy,
    d.RMeet,
    c.Email3 AS CounselorEmail
FROM tblBHSStudent s
JOIN tblBHSHomestay h
    ON s.StudentID = h.StudentID
JOIN tblBHSDormDailyLog d
    ON s.StudentID = d.StudentID
JOIN tblStaff c
    ON s.Counselor = c.FirstName + ' ' + c.LastName
WHERE d.RDate = ?
  AND d.StudyGroup = 'S'
  AND (d.RStudy = 1 OR d.RMeet = 1 OR d.RComment <> '')
  AND (d.SBlock IS NULL OR d.SBlock <> '6:00')
  AND c.SchoolID IN ('BHS','BSS')
  AND c.Email3 <> ''
  AND c.CurrentStaff = 'Y'
ORDER BY
    s.Counselor,
    h.HAdvisor,
    s.LastName,
    s.FirstName
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$thedate]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($rows)) {
    exit('No study hall report data found.');
}

$counselors = [];
$hallAdvisors = [];
$boardingAdmin = [];

foreach ($rows as $r) {
    $htmlRow = buildReportRow($pdo, $r, $semesterStart, $semesterEnd);

    // Counselor group
    $counselorEmail = trim((string)$r['CounselorEmail']);

    if ($counselorEmail !== '') {
        $counselors[$counselorEmail][] = $htmlRow;
    }

    // Hall advisor group
    $hallKey = trim((string)$r['HAdvisor']) . '/' . trim((string)$r['HAdvisor2']);
    $hallAdvisors[$hallKey][] = $htmlRow;

    // Boarding admin full report
    $boardingAdmin[] = $htmlRow;
}

// =======================
// Send to counselors
// =======================

foreach ($counselors as $email => $reportRows) {
    sendReport(
        $from,
        $email,
        "STUDY HALL Report on {$thedate}",
        $reportRows,
        "Here is the STUDY HALL Report on {$thedate}:<br><br>Counselor: " . h($email)
    );
}

// =======================
// Send to hall advisors + HAdvisor2
// =======================

foreach ($hallAdvisors as $group => $reportRows) {
    list($hadvisor, $hadvisor2) = array_pad(explode('/', $group), 2, '');

    $toEmails = [];

    $email1 = getStaffEmailByFullName($pdo, $hadvisor);
    $email2 = getStaffEmailByFullName($pdo, $hadvisor2);

    if ($email1 !== '') {
        $toEmails[] = $email1;
    }

    if ($email2 !== '') {
        $toEmails[] = $email2;
    }

    sendReport(
        $from,
        $toEmails,
        "STUDY HALL Report on {$thedate}",
        $reportRows,
        "Here is the STUDY HALL Report on {$thedate}.<br><br>Youth advisor: " . h($group)
    );
}

// =======================
// Send to boarding admin + 1047
// =======================

$boardingAdminEmails = getEmailListByGroupTeam($pdo, '1003');
$email1047 = getEmailListByGroupTeam($pdo, '1047');

$adminRecipients = array_merge($boardingAdminEmails, $email1047);

sendReport(
    $from,
    $adminRecipients,
    "STUDY HALL Report on {$thedate}",
    $boardingAdmin,
    "Here is the STUDY HALL Report on {$thedate}."
);

echo "Study Hall report sent successfully for {$thedate}.";
