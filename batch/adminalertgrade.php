<?php
require_once __DIR__.'/sendEmailClass.php';

function getCounselorEmail($name) {
  $dsn = "odbc:Driver={SQL Server};Server=10.100.4.6;Database=Bodwell;Uid=web;Pwd=AJgw!cG4nw;";

  // $dsn = "odbc:Driver={SQL Server};Server=10.100.0.5;Database=Bodwell;Uid=devweb;Pwd=9zQjq4WRgkFF;";
  $conn = new PDO($dsn);
  $query = "SELECT *
  FROM tblStaff
  WHERE CONCAT(FirstName, ' ', LastName) = '$name'";
  $stmt = $conn->prepare($query);
  if ($stmt->execute()) {
      $row = $stmt->fetch();
      if($row) {
        return $row['Email3'];
      } else {
        return '';
      }
  }

}
function createsubtreetable($tblheader,$tbl) {

  return "<style>table{border-collapse:collapse;}td{border:2px solid #006100;}</style><table><tr style='text-align:center'><td colspan='3' >$tblheader</td></tr><tr style='background-color:#b4c6e7; text-align:center;'><td>Student Name</td><td>Course Name</td><td>Mark</td></tr>$tbl</table>";
}

function createtree($arr) {
  $i=0;
  $mtbl = '';

  foreach($arr as $x => $val) {
    $maintbl = '';
    $G12tbl = '';
    $G11tbl = '';
    $G10tbl = '';
    $tbl = '';
    for ($i=0; $i < sizeof($val); $i++) {
      $sFirstName = $val[$i]['sFirstName'];
      $sLastName = $val[$i]['sLastName'];
      $sEnglishName = $val[$i]['sEnglishName'];
      $courseName = $val[$i]['courseName'];
      $courseRateScaled = number_format(round($val[$i]['courseRateScaled']*100,1),1);
      // $courseRateScaled = $val[$i]['courseRateScaled'];
      $color = '';
      $fontcolor = '';
      switch ($val[$i]['AlertLevel']) {
        case 'Moderate':
          $color = '#ffc000';
          $fontcolor = 'black';
          break;
        case 'High':
          $color = '#ed7d31';
          $fontcolor = 'black';
          break;
        case 'Critical':
          $color = '#c00000';
          $fontcolor = 'white';
          break;
        default:
          // code...
          break;
      }

      if($val[$i]['CurrentGrade'] == 'Grade 12') {
        $G12tbl .= "<tr style='text-align:center'><td>$sFirstName $sLastName $sEnglishName</td><td>$courseName</td><td style='background-color:$color; color:$fontcolor;text-align:center'>$courseRateScaled</td></tr>";
      } elseif ($val[$i]['CurrentGrade'] == 'Grade 11') {
        $G11tbl .= "<tr style='text-align:center'><td>$sFirstName $sLastName $sEnglishName</td><td>$courseName</td><td style='background-color:$color; color:$fontcolor;text-align:center'>$courseRateScaled</td></tr>";
      } elseif ($val[$i]['CurrentGrade'] == 'Grade 10') {
        $G10tbl .= "<tr style='text-align:center'><td>$sFirstName $sLastName $sEnglishName</td><td>$courseName</td><td style='background-color:$color; color:$fontcolor;text-align:center'>$courseRateScaled</td></tr>";
      } else {
        $tbl .= "<tr style='text-align:center'><td>$sFirstName $sLastName $sEnglishName</td><td>$courseName</td><td style='background-color:$color; color:$fontcolor;text-align:center'>$courseRateScaled</td></tr>";
      }

    }
    $maintbl = createsubtreetable('Grade 12' ,$G12tbl).'<br /><br />'.createsubtreetable('Grade 11',$G11tbl).'<br /><br />'.createsubtreetable('Grade 10',$G10tbl).'<br /><br />'.createsubtreetable('AEP & G8/9',$tbl);
    $from =  array('email' => 'helpdesk@bodwell.edu', 'name' => 'IT Helpdesk');
    $to = array(
      array('email' => $email, 'name' => $x)
    );

    // echo $send;
    $mtbl .= "------------------$x--------------------------<br/>".$maintbl."--------------------------------------------------------------------<br/>";


  }
  // return $maintbl;
  $subject = "Weekly Academic Alert Report";
  $to = array(
    array('email' => 'cathy@bodwell.edu', 'name' => 'Cathy Lee'),
    array('email' => 'sgoobie@bodwell.edu', 'name' => 'Stephen Goobie'),
    array('email' => 'shane.chaffey@bodwell.edu', 'name' => 'Shane Chaffey'),
    array('email' => 'hallis@bodwell.edu', 'name' => 'Housam Hallis'),
    array('email' => 'j_canderan@bodwell.edu', 'name' => 'Jeri Canderan'),
    array('email' => 'marie.alemi@bodwell.edu', 'name' => 'Marie Alemi'),
    array('email' => 'angela.jay@bodwell.edu', 'name' => 'Angela Jay')
  );
$send = sendEmail($from, $to, $cc, $subject, $mtbl, $altBody = '');

}
$dsn = "odbc:Driver={SQL Server};Server=10.100.4.6;Database=Bodwell;Uid=web;Pwd=AJgw!cG4nw;";

// $dsn = "odbc:Driver={SQL Server};Server=10.100.0.5;Database=Bodwell;Uid=devweb;Pwd=9zQjq4WRgkFF;";
$conn = new PDO($dsn);
$query = "SELECT
  studentId,
 sFirstName,
 sLastName,
 sEnglishName,
 counselor,
  courseId,
  courseName,
  COUNT(categoryId) categoryCount,
  SUM(categoryWeight) categoryWeightTotal,
  SUM(categoryRateScaled * categoryWeight) courseRateOrigin,
  SUM(categoryRateScaled * categoryWeight) * (1 / SUM(categoryWeight)) courseRateScaled,
 MAlert,
 HAlert,
 CAlert,
 case
 when SUM(categoryRateScaled * categoryWeight) * (1 / SUM(categoryWeight)) <= CAlert
 then 'Critical'
 when SUM(categoryRateScaled * categoryWeight) * (1 / SUM(categoryWeight)) <= HAlert
 then 'High'
 when SUM(categoryRateScaled * categoryWeight) * (1 / SUM(categoryWeight)) <= MAlert
 then 'Moderate'
 else 'None'
 end as AlertLevel,
 CurrentGrade
FROM (
  SELECT
    student.StudentID studentId,
  student.FirstName sFirstName,
  student.LastName sLastName,
  student.EnglishName sEnglishName,
  student.Counselor counselor,
    course.SubjectID courseId,
    course.SubjectName courseName,
    category.CategoryID categoryId,
    category.CategoryWeight categoryWeight,
    SUM((grade.ScorePoint / item.MaxValue) * item.ItemWeight) * (1 / SUM(item.ItemWeight)) categoryRateScaled,
   CASE
    WHEN course.MAlert = 'B' THEN '0.86'
    WHEN course.MAlert = 'C+' THEN '0.73'
    WHEN course.MAlert = 'C' THEN '0.67'
    WHEN course.MAlert = 'C-' THEN '0.60'
    WHEN course.MAlert = 'F' THEN '0.50'
    ELSE '-1'
   END AS MAlert,
    CASE
    WHEN course.HAlert = 'B' THEN '0.86'
    WHEN course.HAlert = 'C+' THEN '0.73'
    WHEN course.HAlert = 'C' THEN '0.67'
    WHEN course.HAlert = 'C-' THEN '0.60'
    WHEN course.HAlert = 'F' THEN '0.50'
    ELSE '-1'
   END AS HAlert,
   CASE
    WHEN course.CAlert = 'B' THEN '0.86'
    WHEN course.CAlert = 'C+' THEN '0.73'
    WHEN course.CAlert = 'C' THEN '0.67'
    WHEN course.CAlert = 'C-' THEN '0.60'
    WHEN course.CAlert = 'F' THEN '0.50'
    ELSE '-1'
   END AS CAlert,
   student.CurrentGrade CurrentGrade
  FROM tblBHSOGSGrades grade
    JOIN tblBHSOGSCategoryItems item ON grade.CategoryItemID = item.CategoryItemID
    JOIN tblBHSOGSCourseCategory category ON item.CategoryID = category.CategoryID
    JOIN tblBHSSubject course ON category.SubjectID = course.SubjectID
    JOIN tblBHSStudentSubject studentSubject ON grade.StudSubjID = studentSubject.StudSubjID
    JOIN tblBHSStudent student ON studentSubject.StudNum = StudentID
  WHERE grade.SemesterID = (SELECT SemesterID FROM tblBHSSemester WHERE CurrentSemester = 'Y') AND grade.ScorePoint IS NOT NULL AND grade.Exempted <> 1 AND course.GAlert = '1'
  GROUP BY student.StudentID, course.SubjectID, category.CategoryID, category.CategoryWeight, course.subjectName,
 course.MAlert,
    course.HAlert,
    course.CAlert,student.FirstName, student.LastName, student.EnglishName, student.Counselor, student.CurrentGrade
) categoryGrade
GROUP BY studentId, courseId, courseName,sFirstName,sLastName,sEnglishName,counselor,
MAlert, HAlert, CAlert, CurrentGrade
ORDER BY counselor asc, studentId desc";


$stmt = $conn->prepare($query);



if ($stmt->execute()) {
    while ($row = $stmt->fetch()) {
      if($row['AlertLevel'] !== 'None') {
        $arr[$row['counselor']][] = $row;
      }

    }
}

createtree($arr);




 ?>
