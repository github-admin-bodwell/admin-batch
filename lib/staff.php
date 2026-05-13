<?php
/**
 * @author Bryan Lee <yeebwn@gmail.com>
 */

require_once __DIR__.'/pdo-enabled.php';
require_once __DIR__.'/sql.php';

class Staff extends Woonysoft\PDO\PDOEnabled {

    private $staffId;

    /**
     * Student constructor.
     * @param PDO $db
     * @param string $staffId
     */
    function __construct($db, $staffId = '') {
        parent::__construct($db, 'tblStaff');
        $this->staffId = $staffId;
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

    function getStaffById($staffId = null) {
        $staffId = $staffId ? $staffId : $this->staffId;
        $fields = array(
            'StaffID' => 'staffId',
            'FirstName' => 'firstName',
            'LastName' => 'lastName',
            'Email3' => 'email',
            'PositionTitle' => 'positionTitle',
            'RoleBOGS' => 'roleBogs',
            'RoleSYS' => 'roleSYS',
            'AuthBOGS' => 'authBogs',
        );
        $fields = $this->buildSelectList($fields);
        $sql = "SELECT {$fields} FROM tblStaff WHERE StaffID=?";
        return $this->queryOne($sql, array($staffId));
    }

    function updateAuth($staffId = null) {
        $staffId = $staffId ? $staffId : $this->staffId;
        $sql = "UPDATE tblStaff SET AuthBOGS=NULL WHERE StaffID=?";
        $this->execute($sql, array($staffId));
    }

    function getAdminData($termId, $staffId) {
        $term = $this->getTerm($termId);
        $termId = $term['termId'];
        return array(
            'profile' => $this->getStaffById($staffId),
            'term' => $term,
            'activityCategories' => $this->getActivityCategories(),
            'schoolActivities' => $this->getSchoolActivities($termId),
            'studentActivities' => $this->getStudentActivities($termId),
            'instructors' => $this->getInstructors(),
            'activityEnrolls' => $this->getActivityEnrolls($termId),
        );
    }

    /**
     * @param string? $termId
     * @return array
     */
    function getTerm($termId = null) {
        $sql = $this->getSql($termId ? 'term' : 'current-term');
        return $this->queryOne($sql, $termId ? array($termId) : array());
    }

    function getTermInfo() {
      $term = $this->getTerm($termId);
      $termId = $term['termId'];
      $termLabel = $term['termLabel'];
      return array(
          'termId' => $termId,
          'termLabel' => $termLabel,
      );
    }

    function getSchoolActivities($termId) {
        $sql = $this->getSql('school-activity-list');
        $sql = str_replace('{termId}', $termId, $sql);
        return $this->query($sql);
    }

    function getStudentActivities($termId) {
        $sql = $this->getSql('admin-student-activity-list');
        $sql = str_replace('{termId}', $termId, $sql);
        return $this->query($sql);
    }

    function getStudentActivityById($termId, $studentActivityId) {
        $sql = $this->getSql('student-activity-by-id');
        $sql = str_replace('{termId}', $termId, $sql);
        $sql = str_replace('{activityId}', $studentActivityId, $sql);
        return $this->queryOne($sql);
    }

    function saveSchoolActivity($termId, $activity) {
        if(isset($activity['activityId'])) {
            return $this->updateSchoolActivity($termId, $activity);
        } else {
            return $this->insertSchoolActivity($termId, $activity);
        }
    }

    function insertSchoolActivity($termId, $activity) {
        $data = array(
            'SemesterID' => $termId,
            'Title' => $activity['title'],
            'Body' => $activity['description'],
            'ActivityCategory' => $activity['categoryCode'],
            'ActivityType' => $activity['activityType'],
            'MaxEnrolment' => $activity['maxEnroll'],
            'StaffID' => $activity['staffId'],
            'StaffID2' => $activity['staffId2'],
            'Location' => $activity['location'],
            'MeetingPlace' => $activity['meetingPlace'],
            'BaseHours' => $activity['baseHours'],
            'StartDate' => $activity['startDate'],
            'EndDate' => $activity['endDate'],
            //'AllDay' => $activity['allDay'],
            'DPA' => $activity['dpa'],
            'WorkExp' => $activity['workExp'],
            'CreateUserID' => $this->staffId,
            'ModifyUserID' => $this->staffId,
        );
        $this->executeInsert('tblBHSSPActivity', $data);
        return array(
            'schoolActivities' => $this->getSchoolActivities($termId),
        );
    }

    function updateSchoolActivity($termId, $activity) {
        $data = array(
            'Title' => $activity['title'],
            'Body' => $activity['description'],
            'ActivityCategory' => $activity['categoryCode'],
            'ActivityType' => $activity['activityType'],
            'MaxEnrolment' => $activity['maxEnroll'],
            'StaffID' => $activity['staffId'],
            'StaffID2' => $activity['staffId2'],
            'Location' => $activity['location'],
            'MeetingPlace' => $activity['meetingPlace'],
            'BaseHours' => $activity['baseHours'],
            'StartDate' => $activity['startDate'],
            'EndDate' => $activity['endDate'],
            //'AllDay' => $activity['allDay'],
            'DPA' => $activity['dpa'],
            'WorkExp' => $activity['workExp'],
            'ModifyUserID' => $this->staffId,
        );
        $dataStudent = array(
            'Title' => $activity['title'],
            'Body' => $activity['description'],
            'ActivityCategory' => $activity['categoryCode'],
            'ActualLocation' => $activity['location'],
            'ActualHours' => $activity['baseHours'],
            'ActualSDate' => $activity['startDate'],
            'ActualEDate' => $activity['endDate'],
            //'AllDay' => $activity['allDay'],
            'ActualDPA' => $activity['dpa'],
            'ActualWorkExp' => $activity['workExp'],
            'ModifyUserID' => $this->staffId,
        );
        $conditions = array(
            'SemesterID' => $termId,
            'ActivityID' => $activity['activityId']
        );
        $conditionsStudent = array(
            'ActivityID' => $activity['activityId']
        );
        $this->executeUpdate('tblBHSSPActivity', $data, $conditions);
        $this->executeUpdate('tblBHSSPStudentActivities', $dataStudent, $conditionsStudent);
        return array(
            'schoolActivities' => $this->getSchoolActivities($termId),
        );
    }

    function deleteSchoolActivity($termId, $activityId) {
        $conditions = array(
            'SemesterID' => $termId,
            'ActivityID' => $activityId
        );
        $this->executeDelete('tblBHSSPActivity', $conditions);
        $this->executeDelete('tblBHSSPStudentActivities', array('ActivityID'=>$activityId));
        return array(
            'schoolActivities' => $this->getSchoolActivities($termId),
        );
    }

    function updateStudentActivity($termId, $activity) {
        $studentActivityId = $activity['studentActivityId'];
        $data = array(
            'activityStatus' => $activity['activityStatus']
        );
        if(isset($activity['actualHours'])) {
            $data['actualHours'] = $activity['actualHours'];
        }
        if(isset($activity['approverId'])) {
            $data['approverStaffId'] = $activity['approverId'];
        }
        if(is_array($studentActivityId)) {
            foreach($studentActivityId as $id) {
                $conditions = array(
                    //'SemesterID' => $termId,
                    'StudentActivityID' => $id
                );
                $this->executeUpdate('tblBHSSPStudentActivities', $data, $conditions);
            }
        } else {
            $conditions = array(
                //'SemesterID' => $termId,
                'StudentActivityID' => $studentActivityId
            );
            $this->executeUpdate('tblBHSSPStudentActivities', $data, $conditions);
        }
        return array(
            'studentActivities' => $this->getStudentActivities($termId)
        );
    }

    private function getActivityEnrolls($termId) {
        $sql = $this->getSql('school-activity-enrolls');
        return $this->query($sql, array($termId));
    }

}
