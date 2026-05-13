<?php
require_once __DIR__.'/sendEmailClass.php';

$dsn = "odbc:Driver={SQL Server};Server=10.100.4.6;Database=Bodwell;Uid=web;Pwd=AJgw!cG4nw;";
$conn = new PDO($dsn);
$query = "SELECT s.SchoolEmail, s.FirstName, s.LastName, s.EnglishName, c.FirstName as pFname, c.LastName as cLname, c.EmailPersonal FROM tblBHSStudent s
LEFT JOIN tblBHSStudentContact c on c.StudentID = s.StudentID AND c.ContactTab = 'PG1'
WHERE s.StudentID IN (201800315,
201900104,
202000104)";

$stmt = $conn->prepare($query);

$from = array('email' => 'helpdesk@bodwell.edu', 'name' => 'BHS IT Help Desk');


$subject = 'Please disregard the previous email.  Your child had return the laptop.';




if ($stmt->execute()) {
  $i = 0;
    while ($row = $stmt->fetch()) {
      $i++;
      $pfname = $row['pFname'];
      $cLname = $row['cLname'];
      $SchoolEmail = $row['SchoolEmail'];
      $FirstName = $row['FirstName'];
      $LastName = $row['LastName'];
      $EnglishName = $row['EnglishName'];
      $EmailPersonal = $row['EmailPersonal'];

      $cc = array(
        array('email' => $SchoolEmail, 'name' => $FirstName.' '.$LastName)
      );

      $to = array(
        array('email' => $EmailPersonal, 'name' => $pfname.' '.$cLname)
      );


//       $body = "<p>Dear $pfname $cLname,</p>
// <p>&nbsp;</p>
// <p>Hope this email finds you well.</p>
// <p>&nbsp;</p>
// <p>This is to inform you that we need your child, $FirstName $LastName, to return their laptop to the IT office by <strong>3pm PST, June 30<sup>th</sup>, 2021</strong>.&nbsp; Failure to do so will result in a <strong>CAD$1700</strong> charge. We have emailed the students and made multiple announcements and reminders. &nbsp;Most students returned their laptops before yesterday.</p>
// <p>&nbsp;</p>
// <p>Please support us by reminding your child to return their laptop by the time by 3pm tomorrow.</p>
// <p>&nbsp;</p>
// <p>Thank you for your attention.&nbsp; If you have any concerns or questions, please let us know at <a href='mailto:helpdesk@bodwell.edu'>helpdesk@bodwell.edu</a>.</p>
// <p>&nbsp;</p>
// <p><strong>IT Helpdesk</strong></p>
// <p><strong>BODWELL HIGH SCHOOL</strong></p>
// <p>955 Harbourside Drive,&nbsp;North Vancouver, BC&nbsp;V7P&nbsp;3S4,&nbsp;Canada</p>
// <p>Phone: (604) 998-1000 Ext. 2400</p>
// <p>Email: <a href='mailto:helpdesk@bodwell.edu'>helpdesk@bodwell.edu</a></p>
// <p>Website: www.<a href='http://www.bodwell.edu/'>bodwell.edu</a></p>";

      $body = "<p>Dear $pfname $cLname,</p>
<p>&nbsp;</p>
<p>Please disregard the previous email.  Your child had return the laptop.</p>
<p>&nbsp;</p>
<p>Thank you and please let us know at helpdesk@bodwell.edu if you have any questions.</p>
<p>&nbsp;</p>
<p><strong>IT Helpdesk</strong></p>
<p><strong>BODWELL HIGH SCHOOL</strong></p>
<p>955 Harbourside Drive,&nbsp;North Vancouver, BC&nbsp;V7P&nbsp;3S4,&nbsp;Canada</p>
<p>Phone: (604) 998-1000 Ext. 2400</p>
<p>Email: <a href='mailto:helpdesk@bodwell.edu'>helpdesk@bodwell.edu</a></p>
<p>Website: www.<a href='http://www.bodwell.edu/'>bodwell.edu</a></p>";
echo $body;
// $send = sendEmail($from, $to, $cc, $subject, $body, $altBody = '');

   }

}




?>
