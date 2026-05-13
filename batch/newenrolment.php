<?php
require_once __DIR__.'/sendEmailClass.php';

$dsn = "odbc:Driver={SQL Server};Server=10.100.4.6;Database=Bodwell;Uid=web;Pwd=AJgw!cG4nw;";
$pdo = new PDO($dsn);

$dateToday = date('Y-m-d');
$weekday = date('N'); // 1 (Mon) to 7 (Sun)

if ($weekday >= 1 && $weekday <= 7) {
    // Delete today's enrolment data
    $stmt = $pdo->prepare("DELETE FROM tblbhsenrolment WHERE cdate = ?");
    $stmt->execute([$dateToday]);

    // Get all semesters since 2004
    $semesters = $pdo->query("SELECT * FROM tblbhssemester WHERE YEAR(startdate) >= 2004 ORDER BY semesterid DESC")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($semesters as $semester) {
        $sdate = $semester['StartDate'];

        $studentCount = $pdo->query("SELECT COUNT(*) FROM tblbhsstudent WHERE enrolmentdate = '$sdate'")->fetchColumn();
        $rs = $pdo->query("SELECT COUNT(*) FROM tblbhsstudent WHERE enrolmentdate = '$sdate' AND newstudent = 'RS'")->fetchColumn();
        $ta = $pdo->query("SELECT COUNT(*) FROM tblbhsstudent WHERE enrolmentdate = '$sdate' AND newstudent = 'TA'")->fetchColumn();
        $tu = $pdo->query("SELECT COUNT(*) FROM tblbhsstudent WHERE enrolmentdate = '$sdate' AND newstudent = 'TU'")->fetchColumn();
        $tw = $pdo->query("SELECT COUNT(*) FROM tblbhsstudent WHERE enrolmentdate = '$sdate' AND newstudent = 'TW'")->fetchColumn();
        $ap = $pdo->query("SELECT COUNT(*) FROM tblbhsstudent WHERE enrolmentdate = '$sdate' AND newstudent = 'AP'")->fetchColumn();
        $au = $pdo->query("SELECT COUNT(*) FROM tblbhsstudent WHERE enrolmentdate = '$sdate' AND newstudent = 'AU'")->fetchColumn();
        $ww = $pdo->query("SELECT COUNT(*) FROM tblbhsstudent WHERE enrolmentdate = '$sdate' AND newstudent = 'WW'")->fetchColumn();

        $stmt = $pdo->prepare("INSERT INTO tblbhsenrolment (cdate, sdate, au, ap, tw, tu, ta, rs, ww, tt) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$dateToday, $sdate, $au, $ap, $tw, $tu, $ta, $rs, $ww, $studentCount]);
    }

    // BOAS part
    $pdo->prepare("DELETE FROM tblBHSApplicationStatusCount WHERE currentdate = ?")->execute([$dateToday]);

    $semesters = $pdo->query("SELECT SemesterName FROM tblBHSApplicationFeeInfo ORDER BY semesterid DESC")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($semesters as $row) {
        $semName = $row['SemesterName'];

        $apps = $pdo->query("SELECT ApplicationStatus, COUNT(*) AS num FROM tblBHSApplication WHERE SemesterApplied = '$semName' GROUP BY ApplicationStatus")
                    ->fetchAll(PDO::FETCH_ASSOC);

        $inpr = $accp = $acwc = $rgst = $rejc = $canc = 0;
        foreach ($apps as $app) {
            switch ($app['ApplicationStatus']) {
                case 'In Progress': $inpr = $app['num']; break;
                case 'Accepted': $accp = $app['num']; break;
                case 'Accepted with Conditions': $acwc = $app['num']; break;
                case 'Registered': $rgst = $app['num']; break;
                case 'Rejected': $rejc = $app['num']; break;
                case 'Cancelled': $canc = $app['num']; break;
            }
        }

        $stmt = $pdo->prepare("INSERT INTO tblBHSApplicationStatusCount (currentdate, termapplied, inpr, accp, acwc, rgst, rejc, canc) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$dateToday, $semName, $inpr, $accp, $acwc, $rgst, $rejc, $canc]);
    }
}
