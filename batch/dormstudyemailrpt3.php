<?php


require_once __DIR__.'/sendEmailClass.php';

$dsn = "odbc:Driver={SQL Server};Server=10.100.4.6;Database=Bodwell;Uid=web;Pwd=AJgw!cG4nw;";
// $dsn = "odbc:Driver={SQL Server};Server=10.100.0.5;Database=Bodwell;Uid=devweb;Pwd=9zQjq4WRgkFF;";

$pdo = new PDO($dsn);

$thedate = date('Y-m-d');

$dayOfWeek = date('N', strtotime($thedate));



if ($dayOfWeek >= 1 && $dayOfWeek <= 4) { // Monday = 1, Thursday = 4
    $stmt = $pdo->prepare("SELECT a.StudentID, b.LastName, b.FirstName, b.SchoolEmail,
            (SELECT TOP 1 Email3 FROM tblStaff AS d WHERE (FirstName + ' ' + LastName = c.HAdvisor) AND (SchoolID IN ('BHS', 'BSS')) AND (CurrentStaff = 'Y') AND (Email3 <> '')) AS h1email,
            (SELECT TOP 1 Email3 FROM tblStaff AS e WHERE (FirstName + ' ' + LastName = c.HAdvisor2) AND (SchoolID IN ('BHS', 'BSS')) AND (CurrentStaff = 'Y') AND (Email3 <> '')) AS h2email
        FROM tblBHSDormDailyLog AS a
        INNER JOIN tblBHSStudent AS b ON a.StudentID = b.StudentID
        INNER JOIN tblBHSHomestay AS c ON b.StudentID = c.StudentID
        WHERE a.RDate = ? AND a.RStudy = 1 AND a.StudyGroup = 'S' AND a.SBlock <> '6:00'
        ORDER BY b.LastName, b.FirstName");

    $stmt->execute([$thedate]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $to = [['email' => $row['SchoolEmail'], 'name' => $row['FirstName'] . ' ' . $row['LastName']]];

        $cc = [['email' => 'ben.fowler@bodwell.edu', 'name' => 'Ben Fowler']];
        if (!empty($row['h1email'])) {
            $cc[] = ['email' => $row['h1email'], 'name' => 'Hall Advisor 1'];
        }
        if (!empty($row['h2email'])) {
            $cc[] = ['email' => $row['h2email'], 'name' => 'Hall Advisor 2'];
        }

        $html = "Hi, {$row['LastName']}, {$row['FirstName']},<br><br>" .
                "You are receiving this message because you were marked Unexcused from Study Hall this evening. " .
                "Please be aware that 3 absences will result in a weekend leave ban. If you have any questions or believe this is a mistake, " .
                "please speak with your hall advisor, or Mr. Ben Fowler.<br><br>Thank you!";

        sendEmail(
            ['email' => 'ben.fowler@bodwell.edu', 'name' => 'Ben Fowler'],
            $to,
            $cc,
            'Unexcused from Study Hall this evening',
            $html
        );

        // Log the sent email
        $insert = $pdo->prepare("INSERT INTO tblSendEmail (SendTo, SendFrom, Sendcc, SendSubject, SendAttach, SendMessage, SendDate, SchoolID, Sendbcc, SentDate)
            VALUES (?, ?, ?, ?, '', ?, GETDATE(), 'SHE', '', ?)");
        $ccList = 'ben.fowler@bodwell.edu';
        if (!empty($row['h1email'])) $ccList .= ';' . $row['h1email'];
        if (!empty($row['h2email'])) $ccList .= ';' . $row['h2email'];

        $insert->execute([
            $row['SchoolEmail'],
            'ben.fowler@bodwell.edu',
            $ccList,
            'Unexcused from Study Hall this evening',
            $html,
            date('Y-m-d H:i:s')
        ]);
    }
}
