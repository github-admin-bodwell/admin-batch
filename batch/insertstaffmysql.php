<?php
include 'db_connect.php';
$mysqli = db_connect();

$results = $mysqli->query("Truncate table staff");
$i=0;
// insert_db("INSERT INTO staff (StaffID, name, type, email, fob_id, picture, campus_status,hall, time_off) VALUES (
//   'F2178','test','','email','0','','0','hall',0
// )");

$dsn = "odbc:Driver={SQL Server};Server=10.100.4.6;Database=Bodwell;Uid=web;Pwd=AJgw!cG4nw;";

// $dsn = "odbc:Driver={SQL Server};Server=10.100.0.5;Database=Bodwell;Uid=devweb;Pwd=9zQjq4WRgkFF;";
$conn = new PDO($dsn);
$query = "SELECT * FROM tblStaff Where CurrentStaff = 'Y' and RoleBogs in (30,31,32)";
// $query = "SELECT * FROM tblStaff Where CurrentStaff = 'Y' and StaffID = 'F2303'";


$stmt = $conn->prepare($query);

if ($stmt->execute()) {

  while ($row = $stmt->fetch()) {
    $staffid = $row['StaffID'];
    $name = $row['FirstName']." ".$row['LastName'];
    $type = '';
    $email = $row['Email3'];
    $fobid = 0;
    $pic = '';
    $cmapusstatus = 0;
    $hall = '';
    $timeoff = 0;

    insert_db("INSERT INTO staff (StaffID, name, type, email, fob_id, picture, campus_status,hall, time_off) VALUES (
      '$staffid','$name','$type','$email','$fobid','$pic','$cmapusstatus','$hall','$timeoff'
    )");

  }

}



 ?>
