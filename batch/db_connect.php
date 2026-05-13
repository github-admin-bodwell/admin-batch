<?php
function db_connect() {

    // Define connection as a static variable, to avoid connecting more than once
    static $connection;

    // Try and connect to the database, if a connection has not been established yet
    if(!isset($connection)) {
         // Load configuration as an array. Use the actual location of your configuration file
        $config = parse_ini_file('config.ini');
        $connection = mysqli_connect('localhost',$config['username'],$config['password'],$config['dbname']);
    }

    // If connection was not successful, handle the error
    if($connection === false) {
        // Handle error - notify administrator, log to a file, show an error screen, etc.
        echo "did not connect";
    }
    return $connection;
}

function db_query($query) {
    // Connect to the database
    $connection = db_connect();

    // Query the database
    $result = mysqli_query($connection,$query);

    return $result;
}
function db_quote($value) {
    $connection = db_connect();
    return "'" . mysqli_real_escape_string($connection,$value) . "'";
}
function db_error() {
    $connection = db_connect();
    echo mysqli_error($connection);
}
function db_select($query) {
    $rows = array();
    $result = db_query($query);

    // If query failed, return `false`
    if($result === false) {
        return false;
    }

    if($result->num_rows === 0)
    {
        return '0';
    }
    else{
  $rows = mysqli_fetch_array($result, MYSQLI_BOTH);
    return $rows;}
}
function resultToArray($sql) {
$con=db_connect();
$result=mysqli_query($con,$sql);


while( $row = $result->fetch_assoc() ) {
    foreach( $row  AS $value ) {
        $clmNames[] = $value;
    }
    print_r($clmNames[2][1]);
}


}
function insert_db($insert){
// An insertion query. $result will be `true` if successful
$result = db_query($insert);
if($result === false) {
    $error = db_error();
    // Send the error to an administrator, log to a file, etc.
     echo "did not connect";
} else {
    // We successfully inserted a row into the database
}

}
function current_participants($activity_id){
$con=db_connect();
$sql="SELECT * FROM activity_signups WHERE activity_id = $activity_id";
$result=mysqli_query($con,$sql);

    /* determine number of rows result set */
    $row_cnt = $result->num_rows;

  return $row_cnt;

}

function db_count($sql){
    $con=db_connect();
    $result=mysqli_query($con,$sql);

        /* determine number of rows result set */
        $row_cnt = $result->num_rows;

      return $row_cnt;

    }
function update_signup_positions($activity_id){
$con=db_connect();
$sql="SELECT * FROM activity_signups WHERE activity_id = $activity_id ORDER BY timestamp ASC";
$result=mysqli_query($con,$sql);
$position = 0;
  while ($row = $result->fetch_assoc()) {
  		$position ++;
  		$student_id = $row['student_id'];
  		mysqli_query($con,"UPDATE activity_signups SET signup_position = $position WHERE activity_id = $activity_id AND student_id = $student_id");
    }

    }

function signup_position($activity_id, $student_id){
$result = db_query("SELECT signup_position FROM activity_signups WHERE activity_id = $activity_id AND student_id = $student_id");
  while ($row = $result->fetch_assoc()) {
        return $row['signup_position'];
    }

}

function get_students($student_id){

$students = db_select("SELECT * FROM students WHERE student_id = $student_id");
if($students === false) {
    $error = db_error();
    // Handle error - inform administrator, log to file, show error page, etc.
}
return $students;
}

function get_activity($activity_id){

$activities = db_select("SELECT * FROM activities WHERE activity_id = $activity_id");

if($activities === false) {
    $error = db_error();
    // Handle error - inform administrator, log to file, show error page, etc.
}
return $activities;
}


function get_signups($activity_id, $student_id){

$signups = db_select("SELECT * FROM activity_signups WHERE activity_id = $activity_id AND student_id= $student_id");

if($signups === false) {
    $error = db_error();
    // Handle error - inform administrator, log to file, show error page, etc.
}
return $signups;
}
function get_awp($student_id, $date){

$awps = db_select("SELECT * FROM awp WHERE student_id = $student_id AND date = '$date'");
if($awps == 0){
    return false;

    // Handle error - inform administrator, log to file, show error page, etc.
}
else {
return true;
}
}


function update_attended($student_id){

$activities_attended_query = db_select("SELECT COUNT(*) FROM activity_signups WHERE student_id = $student_id AND attended = 1");
$activities_attended = $activities_attended_query[0];
if($activities_attended === false) {
    $error = db_error();
    // Handle error - inform administrator, log to file, show error page, etc.
}
else{
db_query("UPDATE students SET activities_attended = $activities_attended WHERE student_id = $student_id");

}}

function update_vouchers($student_id){

$results = db_query("UPDATE students SET gaming_vouchers = gaming_vouchers + 1 WHERE student_id=$student_id");
}
function createRandomVal($val){
      $chars="abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789,-";
      srand((double)microtime()*1000000);
      $i = 0;
      $pass = '' ;
      while ($i<=$val)
    {
        $num  = rand() % 33;
        $tmp  = substr($chars, $num, 1);
        $pass = $pass . $tmp;
        $i++;
      }
    return $pass;
    }

?>
