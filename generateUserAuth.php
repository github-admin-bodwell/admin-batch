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
require_once __DIR__ . '/lib/db.php';
$conn = getDatabaseConnection();
$query = "SELECT c.LastName as pLName, c.FirstName as pFName, s.* FROM tblBHSStudent s
LEFT JOIN tblBHSStudentContact c on c.StudentID = s.StudentID and c.ContactTab = 'PG1'
WHERE s.CurrentStudent = 'Y' AND s.StudentID NOT IN (SELECT UserID FROM tblBHSUserAuth)";
$rows = $conn->query($query)->fetchAll(PDO::FETCH_ASSOC);
$insert = null;
$count = 0;
foreach ($rows as $row) {
    $pen = str_replace(' ', '', trim((string)($row['PEN'] ?? '')));
    $schoolEmail = str_replace(' ', '', trim((string)($row['SchoolEmail'] ?? '')));
    if ($pen === '' || $schoolEmail === '') continue;
    if ($insert === null) {
        $insert = $conn->prepare("INSERT INTO tblBHSUserAuth
            (LoginID, UserID, FirstName, LastName, LoginIDParent, FirstNameParent,
             LastNameParent, PW1, PW2, PW3, CurrentUser, Category, RoleCode, ModifyUserID, CreateUserID)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    }
    $PW3 = 'A1de134Dfs';
    $insert->execute([
        $schoolEmail, $row['StudentID'], $row['FirstName'], $row['LastName'], $pen,
        $row['pFName'] ?? '', $row['pLName'] ?? '', '', generateRandomPassword(10), $PW3,
        'Y', 20, 20, $row['StudentID'], 'F2178'
    ]);
    $count++;
}
// Do not expose account passwords in scheduler/web output.
echo $count . "Number<br />";