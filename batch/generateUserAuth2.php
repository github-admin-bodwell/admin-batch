<?php

function generateRandomPassword($length=10) {
    $alt = time() % 2;
    $password = strtoupper(chr(rand(65, 72)));
    $password .= strtolower(chr(rand(74, 75)));
    $password .= strtolower(chr(rand(77, 78)));
    $password .= strtolower(chr(rand(80, 90)));
    $password .= rand(2, 9);
    $password .= rand(2, 9);
    $password .= rand(2, 9);
    $password .= rand(2, 9);
    $password .= strtoupper(chr(rand(65, 72)));
    $password .= strtolower(chr(rand(80, 90)));
    $password .= rand(2, 9);
    $password .= rand(2, 9);
    return substr($password,0,$length);
}



$dsn = "odbc:Driver={SQL Server};Server=10.100.4.6;Database=Bodwell;Uid=web;Pwd=AJgw!cG4nw;";
$conn = new PDO($dsn);
$query = "SELECT c.LastName as pLName, c.FirstName as pFName, s.* FROM tblBHSStudent s
LEFT JOIN tblBHSStudentContact c on c.StudentID = s.StudentID and c.ContactTab = 'PG1'
WHERE s.CurrentStudent = 'A' AND s.StudentID NOT IN (SELECT UserID
FROM tblBHSUserAuth)";
$delete_query = "DELETE FROM tblBHSUserAuth WHERE UserID = '{UserID}'";
$update_query = "UPDATE tblBHSUserAuth SET PW2 = '{PW2}' WHERE UserID = '{UserID}'";
$insert_query = "INSERT INTO tblBHSUserAuth
          (LoginID
          ,UserID
          ,FirstName
          ,LastName
          ,LoginIDParent
          ,FirstNameParent
          ,LastNameParent
          ,PW1
          ,PW2
          ,PW3
          ,CurrentUser
          ,Category
          ,RoleCode
          ,ModifyUserID
          ,CreateUserID)
     VALUES
           ('{LoginID}'
           ,'{UserID}'
           ,'{FirstName}'
           ,'{LastName}'
           ,'{LoginIDParent}'
           ,'{FirstNameParent}'
           ,'{LastNameParent}'
           ,'{PW1}'
           ,'{PW2}'
           ,'{PW3}'
           ,'{CurrentUser}'
           ,'{Category}'
           ,'{RoleCode}'
           ,'{ModifyUserID}'
           ,'{CreateUserID}')";

$stmt = $conn->prepare($query);
$tQuery = "";
$tQuery2 = "";


if ($stmt->execute()) {
  $i = 0;
    while ($row = $stmt->fetch()) {
      if(str_replace(' ', '', $row['PEN']) !== '' && str_replace(' ', '', $row['SchoolEmail']) !== ''){
        $insert_query2 = $insert_query;
        $i++;
        $LoginID = str_replace(' ', '', $row['SchoolEmail']);
        $UserID = $row['StudentID'];
        $FirstName = $row['FirstName'];
        $LastName = $row['LastName'];
        $LoginIDParent = str_replace(' ', '', $row['PEN']);
        $FirstNameParent = $row['pFName'];
        $LastNameParent = $row['pLName'];
        $PW1 = '';
        $PW2 = generateRandomPassword(10);
        $PW3 = 'A1de134Dfs';
        $CurrentUser = 'Y';
        $Category = 20;
        $RoleCode = 20;
        $ModifyUserID = $row['StudentID'];
        $CreateUserID = 'F2178';

          $insert_query2=str_replace('{LoginID}', $LoginID, $insert_query2);
          $insert_query2=str_replace('{UserID}', $UserID, $insert_query2);
          $insert_query2=str_replace('{FirstName}', $FirstName, $insert_query2);
          $insert_query2=str_replace('{LastName}', $LastName, $insert_query2);
          $insert_query2=str_replace('{LoginIDParent}', $LoginIDParent, $insert_query2);
          $insert_query2=str_replace('{FirstNameParent}', $FirstNameParent, $insert_query2);
          $insert_query2=str_replace('{LastNameParent}', $LastNameParent, $insert_query2);
          $insert_query2=str_replace('{PW1}', $PW1, $insert_query2);
          $insert_query2=str_replace('{PW2}', $PW2, $insert_query2);
          $insert_query2=str_replace('{PW3}', $PW3, $insert_query2);
          $insert_query2=str_replace('{CurrentUser}', $CurrentUser, $insert_query2);
          $insert_query2=str_replace('{Category}', $Category, $insert_query2);
          $insert_query2=str_replace('{RoleCode}', $RoleCode, $insert_query2);
          $insert_query2=str_replace('{ModifyUserID}', $ModifyUserID, $insert_query2);
          $insert_query2=str_replace('{CreateUserID}', $CreateUserID, $insert_query2);

          $tQuery2 .= $insert_query2."\n";

      }


   }
   $stmt2 = $conn->prepare($tQuery2);
   $stmt2->execute();
   echo $i."Number"."<br />";
   echo $tQuery2;
}




?>
