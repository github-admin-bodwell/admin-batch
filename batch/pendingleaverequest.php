<?php
require_once __DIR__.'/sendEmailClass.php';

$dsn = "odbc:Driver={SQL Server};Server=10.100.4.6;Database=Bodwell;Uid=web;Pwd=AJgw!cG4nw;";
$pdo = new PDO($dsn);

// Get pending leave
$sql1 = "
SELECT
  tblStaff.FirstName + ' ' + tblStaff.LastName AS FullName,
  tblStaffLeave.FDate,
  tblStaffLeave.TDate,
  tblStaffLeave.HDays,
  tblStaffLeave.ComLeave,
  tblStaffLeave.Approver,
  tblStaffLeave.CID,
  tblStaffLeave.SLID,
  tblStaffContract.SStaff,
  CASE tblStaffLeave.LeaveType
    WHEN '1' THEN 'Vacation Leave'
    WHEN '2' THEN 'Away With Duties'
    WHEN '3' THEN 'Compassionate Leave'
    WHEN '4' THEN 'Maternity Leave'
    WHEN '5' THEN 'No Pay Leave'
    WHEN '6' THEN 'Sick Leave'
    WHEN '7' THEN 'Semester Break'
    WHEN '8' THEN 'Other Leave'
    WHEN '9' THEN 'Extra Day Worked Leave'
  END AS LeaveType
FROM tblStaffLeave
INNER JOIN tblStaff ON LEFT(tblStaffLeave.CID, 5) = tblStaff.StaffID
INNER JOIN tblStaffContract ON tblStaffLeave.CID = tblStaffContract.CID
WHERE tblStaffLeave.LeaveStatus = '1'
ORDER BY tblStaff.FirstName, tblStaff.LastName";

$stmt1 = $pdo->query($sql1);
$results1 = $stmt1->fetchAll(PDO::FETCH_ASSOC);

foreach ($results1 as $row) {
    $FullName = $row['FullName'];
    $FDate = $row['FDate'];
    $TDate = $row['TDate'];
    $Hdays = $row['HDays'];
    $CID = $row['CID'];
    $SLID = $row['SLID'];
    $Approver = $row['Approver'];
    $SStaff = $row['SStaff'];
    $LeaveType = $row['LeaveType'];

    $getApproverEmail = $pdo->prepare("SELECT FirstName, LastName, FirstName + ' ' + LastName AS FullName, Email2 FROM tblStaff WHERE StaffID = ?");
    $getApproverEmail->execute([$Approver]);
    $approverInfo = $getApproverEmail->fetch(PDO::FETCH_ASSOC);

    $toEmails = [];

    if ($approverInfo && !empty($approverInfo['Email2'])) {
        $toEmails[] = $approverInfo['Email2'];

        // If sstaff is not same as approver, get supervisor too
        if ($SStaff != $Approver) {
            $getSStaffEmail = $pdo->prepare("SELECT Email2 FROM tblStaff WHERE StaffID = ?");
            $getSStaffEmail->execute([$SStaff]);
            $sstaffRow = $getSStaffEmail->fetch(PDO::FETCH_ASSOC);
            if ($sstaffRow && !empty($sstaffRow['Email2'])) {
                $toEmails[] = $sstaffRow['Email2'];
            }
        }

        $emailTo = implode(';', $toEmails);

        $body = "
        <p style='font-family:arial;color:black;font-size:12px;'>
            Hello {$approverInfo['FirstName']},<br><br>
            A leave request is pending for your approval.<br><br>
        </p>
        <p>
        <table width='480' border='1' cellpadding='3' style='font-family:arial;color:black;font-size:12px;'>
            <tr><td align='right'>Leave Type :</td><td style='color:red;'>$LeaveType</td></tr>
            <tr><td align='right'>From Date :</td><td style='color:red;'>".date('Y-m-d', strtotime($FDate))."</td></tr>
            <tr><td align='right'>To Date :</td><td style='color:red;'>".date('Y-m-d', strtotime($TDate))."</td></tr>
            <tr><td align='right'>Number of Days :</td><td style='color:red;'>$Hdays day(s)</td></tr>
            <tr><td align='right'>Requested By :</td><td style='color:red;'>$FullName</td></tr>
        </table></p>
        <p style='font-family:arial;color:black;font-size:12px;'><br>
        Please visit the link below to update approval status of this leave request.<br><br>
        <a href='https://staff.bodwell.edu/StaffCentre_LinkInMail.cfm?Staffid=".substr($CID, 0, 5)."&CID=$CID&SLID=$SLID&CFGRIDKEY=$SLID'>Open this leave page at the Staff Centre</a><br><br><br></p>
        <p style='font-family:arial;color:gray;font-size:10px;'>
        ________________________________________________________________________<br>
        This is system-generated email, please do not reply.</p>";

        sendEmail([
            'from' => 'ems.admin@bodwell.edu',
            'to' => $emailTo,
            'subject' => "Pending Leave Request Approval - $LeaveType ($FullName)",
            'body' => $body
        ]);
    } else {
        sendEmail([
            'from' => 'keithlee@bodwell.edu',
            'to' => 'keithlee@bodwell.edu',
            'subject' => "No approver - $LeaveType ($FullName)",
            'body' => "$FullName has no approver."
        ]);
    }
}

// Get pending Extra Day Worked
$sql2 = "
SELECT
  tblStaff.FirstName + ' ' + tblStaff.LastName AS FullName,
  tblStaff.StaffID,
  tblStaffExtraDayWorked.EDWID,
  tblStaffExtraDayWorked.CID,
  tblStaffExtraDayWorked.EDWDate,
  tblStaffExtraDayWorked.ExtraDayWorked,
  tblStaffExtraDayWorked.Approver,
  tblStaffExtraDayWorked.CreateDate,
  tblStaffExtraDayWorked.EDWReason
FROM tblStaffExtraDayWorked
INNER JOIN tblStaff ON LEFT(tblStaffExtraDayWorked.CID, 5) = tblStaff.StaffID
WHERE tblStaffExtraDayWorked.EDWStatus = '1'
ORDER BY tblStaff.FirstName, tblStaff.LastName";

$stmt2 = $pdo->query($sql2);
$results2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);

foreach ($results2 as $row) {
    $FullName = $row['FullName'];
    $StaffID = $row['StaffID'];
    $EDWID = $row['EDWID'];
    $CID = $row['CID'];
    $EDWDate = $row['EDWDate'];
    $ExtraDayWorked = $row['ExtraDayWorked'];
    $Approver = $row['Approver'];
    $CreateDate = $row['CreateDate'];
    $EDWReason = $row['EDWReason'];

    $getApproverEmail = $pdo->prepare("SELECT FirstName, LastName, FirstName + ' ' + LastName AS FullName, Email2 FROM tblStaff WHERE StaffID = ?");
    $getApproverEmail->execute([$Approver]);
    $approverInfo = $getApproverEmail->fetch(PDO::FETCH_ASSOC);

    if ($approverInfo && !empty($approverInfo['Email2'])) {
        $plural = ($ExtraDayWorked > 1) ? 's' : '';

        $body = "
        <p style='font-family:arial;color:black;font-size:12px;'>
            Hello {$approverInfo['FirstName']},<br><br>
            A reporting extra day worked request is pending for your approval.<br><br>
        </p>
        <p>
        <table width='480' border='1' cellpadding='3' style='font-family:arial;color:black;font-size:12px;'>
            <tr><td align='right'>Date :</td><td style='color:red;'>".date('Y-m-d', strtotime($EDWDate))."</td></tr>
            <tr><td align='right'>Extra day worked :</td><td style='color:red;'>$ExtraDayWorked day$plural</td></tr>
            <tr><td align='right'>Reason :</td><td style='color:red;'>$EDWReason</td></tr>
            <tr><td align='right'>Reported By :</td><td style='color:red;'>$FullName (".date('Y-m-d', strtotime($CreateDate)).")</td></tr>
        </table></p>
        <p style='font-family:arial;color:black;font-size:12px;'><br>
        Please visit Staff Centre to update approval status of this extra day worked.<br><br>
        <a href='http://staff.bodwell.edu/StaffCentre_LinkInMail2.cfm?Staffid=$StaffID&CID=$CID&EDWID=$EDWID'>Open this page at the Staff Centre</a><br><br><br></p>
        <p style='font-family:arial;color:gray;font-size:10px;'>
        ________________________________________________________________________<br>
        This is system-generated email, please do not reply.</p>";

        sendEmail([
            'from' => 'ems.admin@bodwell.edu',
            'to' => $approverInfo['Email2'],
            'subject' => "Pending for extra day worked approval ($FullName)",
            'body' => $body
        ]);
    } else {
        sendEmail([
            'from' => 'keithlee@bodwell.edu',
            'to' => 'keithlee@bodwell.edu',
            'subject' => "No approver - extra day worked ($FullName)",
            'body' => "$FullName has no email."
        ]);
    }
}
?>
