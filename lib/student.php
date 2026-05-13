<?php
/**
 * @author Bryan Lee <yeebwn@gmail.com>
 */

require_once __DIR__.'/pdo-enabled.php';
require_once __DIR__.'/sql.php';
require_once __DIR__.'/send-email.php';

class Student extends Woonysoft\PDO\PDOEnabled {

    private $studentId;

    /**
     * Student constructor.
     * @param PDO $db
     * @param string $studentId
     */
    function __construct($db, $studentId = '') {
        parent::__construct($db, 'tblBHSStudent');
        $this->studentId = $studentId;
    }

    /**
     * @param $name
     * @return mixed
     */
    protected function onDefaultValue($name) {
        return false;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return true|array
     */
    protected function onValidate($name, $value) {
        return true;
    }

    function getSql($name) {
        global $_SQL;
        return $_SQL[$name];
    }

    /**
     * @param $email
     * @return array
     * @throws BadRequest
     * @throws Exception
     */
    private function getProfileByEmail($email) {
        $sql = $this->getSql('profile-by-email');
        $profile = $this->queryOne($sql, array($email));
        if(!$profile) {
            throw new BadRequest('Invalid email');
        }
        $term = $this->getTerm();
        $termId = $term['termId'];
        $sql = $this->getSql('num-terms-by-id');
        $numTerms = $this->queryOne($sql, array($profile['studentId'], $termId));
        $sql = $this->getSql('num-aep-terms-by-id');
        $numAEPTerms = $this->queryOne($sql, array($profile['studentId'], $termId));
        return array_merge($profile, $numTerms, $numAEPTerms);
    }

    private function generateHash($password, $cost=12){
        /* To generate the salt, first generate enough random bytes. Because
         * base64 returns one character for each 6 bits, the we should generate
         * at least 22*6/8=16.5 bytes, so we generate 17. Then we get the first
         * 22 base64 characters
         */
        $salt=substr(base64_encode(openssl_random_pseudo_bytes(17)),0,22);
        /* As blowfish takes a salt with the alphabet ./A-Za-z0-9 we have to
         * replace any '+' in the base64 string with '.'. We don't have to do
         * anything about the '=', as this only occurs when the b64 string is
         * padded, which is always after the first 22 characters.
         */
        $salt=str_replace("+",".",$salt);
        /* Next, create a string that will be passed to crypt, containing all
         * of the settings, separated by dollar signs
         */
        $param='$'.implode('$',array(
                "2y", //select the most secure version of blowfish (>=PHP 5.3.7)
                str_pad($cost,2,"0",STR_PAD_LEFT), //add the cost in two digits
                $salt //add the salt
            ));

        //now do the actual hashing
        return crypt($password,$param);
    }

    private function hashEquals($str1, $str2) {
        if(strlen($str1) != strlen($str2)) {
            return false;
        } else {
            $res = $str1 ^ $str2;
            $ret = 0;
            for($i = strlen($res) - 1; $i >= 0; $i--) $ret |= ord($res[$i]);
            return !$ret;
        }
    }

    /**
     * @example
     * $student = new Student();
     * $profile = $student->verify(array('username'=>'bryan@example.com', 'password'=>'secret'));
     * if(!profile) {
     *     throw new Exception('Invalid credential');
     * }
     * @param array $credential array('username'=>'{$username}', 'password'=>'{$password}')
     * @return array
     * @throws BadRequest
     * @throws Unauthorized
     * @throws Exception
     */
    function verify($credential) {
        $sql = $this->getSql('student-password-by-email');
        $email = $credential['username'];
        $password = $credential['password'];
        $res = $this->queryOne($sql, array($email));
        if(!$res) {
            throw new BadRequest('Invalid login id!');
        }
        if($res['password'] === null) {
            throw new BadRequest('Password not set yet!');
        }
        $hash = $res['password'];
        //echo $hash;
        if(!$this->hashEquals($hash, crypt($password, $hash))) {
            throw new Unauthorized();
        }
        return $this->getProfileByEmail($email);
    }

    /**
     * @param $input
     * @return array
     * @throws BadRequest
     * @throws Exception
     */
    function resetPassword($input) {
        $password = $input['password'];
        $password2 = $input['password2'];
        $email = $input['username'];
        $token = $input['token'];
        if($token) {
            // If token exists, token must be validated.
            // Token expires in 10 minutes.
            $now = date('Y-m-d H:i:s');
            $sql = "SELECT EmailID email, DATEDIFF(second, CreateDate, '{$now}') secondsElapsed FROM tblBHSUserAuthToken WHERE Token='{$token}'";
            $res = $this->queryOne($sql);
            if(!$res) {
                throw new BadRequest('Invalid reset token!');
            }
            if($res['secondsElapsed'] > 1800) { // 600 seconds(10 minutes)
                throw new BadRequest('Token expired!');
            }
        }
        if($password != $password2) {
            throw new BadRequest('Password not match!');
        }
        if(strlen($password) < 6) {
            throw new BadRequest('Password too short!');
        }
        if($token) {
            $sql = "UPDATE tblBHSUserAuth SET PW1=?,ModifyUserID=UserID,ModifyDate=getdate() WHERE LoginID=?";
        } else {
            //$sql = "UPDATE tblBHSUserAuth SET PW1=?,ModifyUserID=UserID,ModifyDate=getdate() WHERE LoginID=? AND PW1 IS NULL";
            throw new BadRequest('Need valid reset token!');
        }
        $hash = $this->generateHash($password);
        $count = $this->executeAndCount($sql, array($hash, $email));
        if($count != 1) {
            throw new BadRequest('Failed to reset password!'.$sql);
        }
        return $this->getProfileByEmail($email);
    }

    /**
     * @param $studentId
     * @return array
     * @throws Exception
     */
    function getProfile($studentId = null) {
        $term = $this->getTerm();
        $termId = $term['termId'];
        $sql = $this->getSql('num-terms-by-id');
        $numTerms = $this->queryOne($sql, array($studentId ? $studentId : $this->studentId, $termId));
        $sql = $this->getSql('num-aep-terms-by-id');
        $numAEPTerms = $this->queryOne($sql, array($studentId ? $studentId : $this->studentId, $termId));
        $sql = $this->getSql('profile-by-id');
        $res = $this->queryOne($sql, array($studentId ? $studentId : $this->studentId));
        if($res) {
            return array_merge($res, $numTerms, $numAEPTerms);
        }
        return $res;
    }

    /**
     * @param string? $termId
     * @return array
     * @throws Exception
     */
    function getTerm($termId = null) {
        $sql = $this->getSql($termId ? 'term' : 'current-term');
        return $this->queryOne($sql, $termId ? array($termId) : array());
    }

    function getStudentTerms($termId, $studentId) {
        $sql = $this->getSql('student-terms');
        return $this->query($sql, array($studentId, $termId));
    }

    /**
     * @param string? $termId
     * @return array
     * @throws Exception
     */
    function getCourseData($termId = null) {
        $studentId = $this->studentId;
        if(!$studentId) {
            return array();
        }
        $term = $this->getTerm($termId);
        $termId = $term['termId'];
        $studentTerms = $this->getStudentTerms($termId, $studentId);
        $courseList = $this->getCourseList($termId);
        $courseIdList = array();
        foreach($courseList as $course) {
            $courseIdList[] = $course['courseId'];
        }
        $grades = $this->getGradeList($termId);
        return array(
            'term' => $term,
            'studentTerms' => $studentTerms,
            'courses' => $courseList,
            'categories' => $this->getCategoryList($termId),
            'items' => $this->getItemList($termId),
            'grades' => $grades,
            'itemAverages' => $this->getItemAverageList($courseIdList),
            'courseAverages' => $this->getCourseAverageList($courseIdList),
            'categoryGrades' => $this->getCategoryGradeList($termId),
            'courseGrades' => $this->getCourseGradeList($termId),
            'midcutCourseGrades' => $this->getMidcutCourseGradeList($termId, $term),
            //'schoolActivities' => $this->getSchoolActivityList($termId),
            'studentActivities' => $this->getStudentActivityList($termId),
            //'activityEnrolls' => $this->getActivityEnrolls($termId),
            'activityCategories' => $this->getActivityCategories(),
            //'instructors' => $this->getInstructors(),
        );
    }

    /**
     * @param $termId
     * @param $activityId
     * @return bool
     * @throws Exception
     */
    function enroll($termId, $activityId) {
        if(!is_numeric($termId)) {
            throw new Exception("Invalid Term ID");
        }
        if(!is_numeric($activityId)) {
            throw new Exception("Invalid Activity ID");
        }
        $activity = $this->getActivityById($termId, $activityId);
        if(!$activity) {
            throw new Exception("Activity not found");
        }
        $activityCount = $this->getStudentActivityCount($activityId);
        if($activityCount > 0) {
            throw new Exception('Already registered');
        }
        $activity['activityStatus'] = '10';
        $this->insertSchoolActivity($termId, $activity);
        return true;
    }

    /**
     * @param $termId
     * @param $input
     * @return array
     * @throws Exception
     */
    function submit($termId, $input) {
        if(!$termId) {
            throw new Exception("Invalid term {$termId}");
        }
        if(isset($input['studentActivityId'])) {
            $res = $this->submitSchoolActivityHours($termId, $input);
        } else {
            $res = $this->submitOtherActivityHours($termId, $input);
        }
        $this->sendEmailToApprover($termId, $input);
        return $res;
    }

    /**
     * @param $termId
     * @param $studentActivityId
     * @return array
     * @throws Exception
     */
    private function getStudentActivityById($termId, $studentActivityId) {
        $sql = $this->getSql('student-activity-by-id');
        $sql = str_replace('{termId}', $termId, $sql);
        $sql = str_replace('{activityId}', $studentActivityId, $sql);
        //echo $sql;
        return $this->queryOne($sql);
    }

    /**
     * @param $staffId
     * @return array
     * @throws Exception
     */
    private function getStaffInfo($staffId) {
        $sql = "SELECT StaffId staffId, CONCAT(FirstName, ' ', LastName) AS displayName, FirstName firstName, LastName lastName, Email3 email, PositionTitle positionTitle FROM tblStaff WHERE StaffID = '{$staffId}'";
        return $this->queryOne($sql);
    }

    /**
     * @param $categoryCode
     * @return array
     * @throws Exception
     */
    private function getActivityCategoryByCode($categoryCode) {
        $sql = "SELECT ActivityCategory, Title, Body FROM tblBHSSPActivityConfig WHERE ActivityCategory='{$categoryCode}'";
        return $this->queryOne($sql);
    }

    /**
     * @param $profile
     * @param $studentActivity
     * @return string
     * @throws Exception
     */
    private function getEmailBodyForApprover($profile, $studentActivity) {
        $studentName = "{$profile['firstName']}, {$profile['lastName']} ({$profile['studentId']})";
        if(isset($studentActivity['categoryTitle']) && isset($studentActivity['categoryDescription'])) {
            $activityCategory = "{$studentActivity['categoryTitle']} ({$studentActivity['categoryDescription']})";
        } else {
            $category = $this->getActivityCategoryByCode($studentActivity['categoryCode']);
            $activityCategory = "{$category['Title']} ({$category['Body']})";
        }
        $activityTitle = $studentActivity['title'] ? $studentActivity['title'] : $studentActivity['titleAlt'];
        $date = new DateTime($studentActivity['startDate']);
        $startTime = $date->format('Y-m-d H:i');
        $hours = $studentActivity['baseHours'];
        $qualifiedFor = '';
        if($studentActivity['dpa']) {
            $qualifiedFor .= $qualifiedFor ? ', DPA' : 'DPA';
        }
        if($studentActivity['workExp']) {
            $qualifiedFor .= $qualifiedFor ? ', WECS' : 'WECS';
        }
        $body = <<<BODY
          <style>
            .---label {text-align:right;padding-right:1em;color:#999;font-size:.9em;}
            .---value {}
          </style>
          <table>
            <tr><td class="---label">Student</td><td>{$studentName}</td></tr>
            <tr><td class="---label">Category</td><td>{$activityCategory}</td></tr>
            <tr><td class="---label">Activity</td><td>{$activityTitle}</td></tr>
            <tr><td class="---label">Start Date/Time</td><td>{$startTime}</td></tr>
            <tr><td class="---label">Number of Hours</td><td>{$hours}</td></tr>
            <tr><td class="---label">Qualified For</td><td>{$qualifiedFor}</td></tr>
          </table>
          <p><a href="https://admin.bodwell.edu">Sign in to Admin System to update approval status.</a></p>
BODY;
        return $body;
    }

    /**
     * @param $termId
     * @param $input
     * @return array|bool
     * @throws Exception
     */
    private function sendEmailToApprover($termId, $input) {

        global $settings;
        $env = $settings['env'];
        if(isset($input['studentActivityId'])) {
            $studentActivityId = $input['studentActivityId'];
            $studentActivity = $this->getStudentActivityById($termId, $studentActivityId);
            if(!$studentActivity) {
                throw new Exception("Invalid student activity({$studentActivityId})");
            }
            $approverId = $studentActivity['approverId'];
            $approver = $this->getStaffInfo($approverId);
            if(!$studentActivity) {
                throw new Exception("Invalid staff({$approverId})");
            }
        } else {
            $studentActivity = $input;
            $approverId = $studentActivity['approverId'];
            $approver = $this->getStaffInfo($approverId);
        }
        $from = array('email' => 'sp.admin@bodwell.edu', 'name' => 'BHS Student Portal Admin');
        $to = array(
            array('email' => $approver['email'], 'name' => $approver['displayName'])
        );
        $cc = array();
        $profile = $_SESSION['profile'];
        $studentName = $profile['lastName'] . ', ' . $profile['firstName'];
        $subject = "Activity hours have been submitted by {$studentName}";
        $body = $this->getEmailBodyForApprover($profile, $studentActivity);
        if($env == 'production' || $env == 'staging') {
            if(sendEmail($from, $to, $cc, $subject, $body) !== true) {
                throw new Exception('Failed to send email to '.$approver['displayName']);
            }
            return true;
        } else {
            return array(
                'from' => $from,
                'to' => $to,
                'cc' => $cc,
                'subject' => $subject,
                'body' => $body,
            );
        }

    }

    private function getCourseList($termId) {
        global $_SQL;
        $studentId = $this->studentId;
        $sql = $_SQL['course-list-by-student'];
        $courseList = $this->query($sql, array($termId, $studentId));
        $courseArray = array();
        foreach($courseList as $i => $course) {
            $courseArray[] = $course['courseId'];
        }
        $sql = $_SQL['absence-list-by-student'];
        $sql = str_replace('{courseList}', implode(',', $courseArray), $sql);
        $absenceList = $this->query($sql);
        foreach($absenceList as $absence) {
            foreach($courseList as $i => $course) {
                if($course['studentCourseId'] == $absence['studentCourseId']) {
                    $courseList[$i]['absenceCount'] = $absence['absenceCount'];
                    $courseList[$i]['lateCount'] = $absence['lateCount'];
                }
            }
        }
        return $courseList;
    }

    private function getCategoryList($termId) {
        $studentId = $this->studentId;
        $sql = $this->getSql('category-list-by-student');
        return $this->query($sql, array($termId, $studentId));
    }

    private function getItemList($termId) {
        $studentId = $this->studentId;
        $sql = $this->getSql('item-list-by-student');
        return $this->query($sql, array($termId, $studentId));
    }

    private function getGradeList($termId) {
        $studentId = $this->studentId;
        $sql = $this->getSql('grade-list-by-student');
        return $this->query($sql, array($termId, $studentId));
    }

    private function getItemAverageList($courses) {
        if($courses) {
            $courseList = array();
            foreach($courses as $courseId) {
                $courseList[] = "'{$courseId}'";
            }
            $sql = $this->getSql('item-average-list');
            $sql = str_replace('{courseList}', implode(',', $courseList), $sql);
            return $this->query($sql);
        } else {
            return array();
        }
    }

    private function getCourseAverageList($courses) {
        if($courses) {
            $courseList = array();
            foreach($courses as $courseId) {
                $courseList[] = "'{$courseId}'";
            }
            $sql = $this->getSql('course-average-list');
            $sql = str_replace('{courseList}', implode(',', $courseList), $sql);
            return $this->query($sql);
        } else {
            return array();
        }
    }

    private function getCategoryGradeList($termId) {
        $studentId = $this->studentId;
        $sql = $this->getSql('category-grade-list');
        return $this->query($sql, array($termId, $studentId));
    }

    private function getCourseGradeList($termId) {
        $studentId = $this->studentId;
        $sql = $this->getSql('course-grade-list');
        $sql = str_replace('{termId}', $termId, $sql);
        $sql = str_replace('{studentId}', $studentId, $sql);
        return $this->query($sql);
    }

    private function getMidcutCourseGradeList($termId, $term) {
        $studentId = $this->studentId;
        $midcutDate = $term['midCutoffDate'];
        $sql = $this->getSql('midcut-course-grade-list');
        $sql = str_replace('{termId}', $termId, $sql);
        $sql = str_replace('{studentId}', $studentId, $sql);
        $sql = str_replace('{midcutDate}', "{$midcutDate} 23:59:59.999", $sql);
        return $this->query($sql);
    }

    private function getStudentActivityList($termId) {
        $studentId = $this->studentId;
        $sql = $this->getSql('student-activity-list-v2');
        //$sql = str_replace('{termId}', $termId, $sql);
        $sql = str_replace('{studentId}', $studentId, $sql);
        return $this->query($sql);
    }

    private function getSchoolActivityList($termId) {
        $sql = $this->getSql('school-activity-list');
        $sql = str_replace('{termId}', $termId, $sql);
        return $this->query($sql);
    }

    private function getActivityEnrolls($termId) {
        $sql = $this->getSql('school-activity-enrolls');
        return $this->query($sql, array($termId));
    }

    /**
     * @param $termId
     * @param $activityId
     * @return array
     * @throws Exception
     */
    private function getActivityById($termId, $activityId) {
        $sql = $this->getSql('school-activity-by-id');
        $sql = str_replace('{termId}', $termId, $sql);
        $sql = str_replace('{activityId}', $activityId, $sql);
        //echo $sql;
        return $this->queryOne($sql);
    }

    /**
     * @param $activityId
     * @return mixed
     * @throws Exception
     */
    private function getStudentActivityCount($activityId) {
        $sql = $this->getSql('student-activity-count');
        $sql = str_replace('{studentId}', $this->studentId, $sql);
        $sql = str_replace('{activityId}', $activityId, $sql);
        $res = $this->queryOne($sql);
        return $res['activityCount'];
    }

    private function submitSchoolActivityHours($termId, $activity) {
        $data = array(
            'ActualHours' => $activity['baseHours'],
            'ActivityStatus' => '20',
            'ApproverStaffID' => $activity['approverId']
        );
        $conditions = array(
            'StudentActivityID' => $activity['studentActivityId']
        );
        $this->executeUpdate('tblBHSSPStudentActivities', $data, $conditions);
        return array(
            'studentActivities' => $this->getStudentActivityList($termId),
        );
    }

    /**
     * @param $termId
     * @param $input
     * @return array
     * @throws Exception
     */
    private function submitOtherActivityHours($termId, $input) {
        if(!is_numeric($termId)) {
            throw new Exception("Invalid Term ID");
        }
        $input['activityStatus'] = '20';
        $this->insertSchoolActivity($termId, $input);
        return array(
            'studentActivities' => $this->getStudentActivityList($termId),
        );
    }

    private function insertSchoolActivity($termId, $activity) {
        $now = date('Y-m-d H:i:s');
        $data = array(
            'SemesterID' => $termId,
            'ActivityStatus' => $activity['activityStatus'],
            'ActivityCategory' => $activity['categoryCode'],
            'StudentID' => $this->studentId,
            'ActivityID' => isset($activity['activityId']) ? $activity['activityId'] : null,
            'ActualLocation' => isset($activity['location']) ? $activity['location'] : null,
            'ActualSDate' => $activity['startDate'],
            'ActualEDate' => $activity['endDate'],
            'ActualHours' => $activity['baseHours'],
            'ActualAllDay' => isset($activity['allDay']) ? $activity['allDay'] : '0',
            'ActualDPA' => $activity['dpa'],
            'ActualWorkExp' => $activity['workExp'],
            'ActualCommService' => '0',
            'Witness' => $activity['witness'],
            'ApproverStaffID' => isset($activity['approverId']) ? $activity['approverId'] : null,
            'ModifyDate' => $now,
            'CreateDate' => $now,
            'CreateUserID' => $this->studentId,
            'ModifyUserID' => $this->studentId,
        );
        if($activity['title']) {
            $data['Title'] = $activity['title'];
        }
        if($activity['description']) {
            $data['Body'] = $activity['description'];
        }
        $this->executeInsert('tblBHSSPStudentActivities', $data);
        return true;
    }

    private function getResetToken($email) {

        $now = date('Y-m-d H:i:s');
        $token = bin2Hex(openssl_random_pseudo_bytes(64));
        $data = array(
            'EmailID' => $email,
            'Token' => $token,
            'CreateDate' => $now,
        );
        $this->executeInsert('tblBHSUserAuthToken', $data);
        return $token;

    }

    /**
     * @param $input
     * @return array
     * @throws Exception
     */
    private function getStudentProfileFromResetInfo($input) {
        //$sql = "SELECT ";
        $studentId = $input['studentId'];
        //$firstName = $input['firstName'];
        //$lastName = $input['lastName'];
        $dob = $input['dob'];
        $sql = $this->getSql('profile-by-reset-info');
        return $this->queryOne($sql, array($studentId, $dob));
    }

    /**
     * @param $input
     * @param $resetUrl
     * @param $env
     * @return array|bool
     * @throws BadRequest
     * @throws Exception
     */
    function requestReset($input, $resetUrl, $env) {

        $profile = $this->getStudentProfileFromResetInfo($input);
        if(!$profile) {
            throw new BadRequest('Invalid student information!');
        }
        $email = $profile['schoolEmail'];
        $firstName = $profile['firstName'];
        $lastName = $profile['lastName'];
        $token = $this->getResetToken($email);
        $from = array('email' => 'helpdesk@bodwell.edu', 'name' => 'BHS IT Help Desk');
        $to = array(
            array('email' => $email, 'name' => "{$firstName} {$lastName}")
        );
        $cc = array();
        $subject = 'Bodwell Student Portal Password Reset';
        $url = "{$resetUrl}?token={$token}&email={$email}";
        $body = <<<BODY
            <p>Hi {$firstName},</p>
            <p>We've received a request to reset your password for your Bodwell Student Portal account.
            Click the button below to reset it.
            If you did not request a password reset, please ignore this mail and let us know by replying to this email.</p> 
            <p><a href="{$url}">Reset your password</a></p>
            <p>This password reset link is valid for the next 30 minutes.</p>
            <p>
            Thanks!<br/>
            IT Help Desk
            </p>
            <p>
            Bodwell High School<br/>
            Room 244 (M-F/9am-5pm)<br/>
            helpdesk@bodwell.edu (24/7)<br/>
            604-998-2400 (x2400)<br/>
            </p>
BODY;
        if($env == 'production' || $env == 'staging') {
            $res = sendEmail($from, $to, $cc, $subject, $body);
            if($res !== true) {
                $resJson = json_encode($res);
                throw new Exception("Failed to send reset token. (from: {$from['email']} {$from['name']}, to: {$to[0]['email']} {$to[0]['name']}, url: {$url}, error:".$resJson);
            }
            return true;
        } else {
            return array(
                'from' => $from,
                'to' => $to,
                'cc' => $cc,
                'subject' => $subject,
                'body' => $body,
                'url' => $url
            );
        }

    }

}
