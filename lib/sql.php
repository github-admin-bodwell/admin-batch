<?php
/**
 * @author Bryan Lee <yeebwn@gmail.com>
 */

namespace Bodwell\StudentPortal\Student\SQL;

require_once __DIR__.'/../settings.php';

global $settings;

function _getDateField($name, $alias, $_is_mysql) {
    return $_is_mysql ? "DATE_FORMAT({$name}, '%Y-%m-%d') {$alias}" : "CONVERT(CHAR(10), {$name}, 126) {$alias}";
}

$_is_mysql = $settings['pdo']['database'] == 'mysql';
$_maxValueName = $_is_mysql ? '`MaxValue`' : 'MaxValue';
$_nowFuncName = $_is_mysql ? 'NOW()' : 'GETDATE()';
$_assignDate = _getDateField('item.AssignDate', 'assignDate', $_is_mysql);
$_dueDate = _getDateField('item.DueDate', 'dueDate', $_is_mysql);
$_startDate = _getDateField('term.StartDate', 'startDate', $_is_mysql);
$_endDate = _getDateField('term.EndDate', 'endDate', $_is_mysql);
$_midCutoffDate = _getDateField('term.MidCutOffDate', 'midCutoffDate', $_is_mysql);

$termFields = "
    SemesterID termId,
    SemesterName termLabel,
    CurrentSemester currentSemester,
    {$_startDate},
    {$_endDate},
    {$_midCutoffDate}
";

$basicProfileFields = "
    student.StudentID studentId,
    student.PEN pen,
    student.FirstName firstName,
    student.LastName lastName,
    student.EnglishName englishName,
    student.SchoolEmail schoolEmail,
    student.CurrentGrade currentGrade
";

$profileFields = "
    {$basicProfileFields},
    student.Counselor counsellor,
    student.Mentor mentor,
    student.Houses houses,
    homestay.Homestay homestay,
    homestay.Residence residence,
    homestay.Halls halls,
    homestay.RoomNo roomNo,
    homestay.Hadvisor youthAdvisor,
    homestay.Hadvisor2 youthAdvisor2,
    homestay.Tutor tutor
";

$activityFields = "
    category.ActivityCategory category,
    category.Title categoryTitle,
    category.Body categoryDescription,
    activity.SemesterID termId,
    activity.ActivityID activityId,
    activity.Title title,
    activity.Body description,
    activity.StaffID staffId,
    CONCAT(staff.FirstName, ' ', staff.LastName) staffName,
    activity.StaffID2 staffId2,
    CONCAT(staff2.FirstName, ' ', staff2.LastName) staff2Name,
    activity.Location location,
    activity.BaseHours baseHours,
    activity.StartDate startDate,
    activity.EndDate endDate,
    activity.AllDay allDay,
    activity.DPA dpa,
    activity.VLWE workExp,
    activity.MaxEnrolment maxEnroll
";

$studentActivityFields = "
    studentActivity.StudentActivityID studentActivityId,
    studentActivity.ActivityStatus activityStatus,
    studentActivity.StudentID studentId,
    studentActivity.Title titleAlt,
    studentActivity.Body descriptionAlt,
    studentActivity.Location actualLocation,
    studentActivity.SDate actualStartDate,
    studentActivity.EDate actualEndDate,
    studentActivity.Hours actualHours,
    studentActivity.AllDay actualAllDay,
    studentActivity.DPA actualDpa,
    studentActivity.VLWE actualWorkExp,
    studentActivity.ApproverStaffID approverId,
    studentActivity.SelfSubmitWitness witness
";

$_SQL = array(

    'term-list' => "SELECT {$termFields} FROM tblBHSSemester term ORDER BY SemesterID DESC",
    'term' => "SELECT {$termFields} FROM tblBHSSemester term WHERE SemesterID=?",
    'current-term' => "SELECT {$termFields} FROM tblBHSSemester term WHERE CurrentSemester='Y'",

    'profile-by-email-password' => "
        SELECT {$profileFields}
        FROM tblBHSStudent student
        LEFT JOIN tblBHSHomestay homestay ON student.StudentID = homestay.StudentID
        WHERE student.SchoolEmail=? AND student.Password=? AND student.CurrentStudent='Y'
    ",

    'profile-by-id-password' => "
        SELECT {$profileFields}
        FROM tblBHSStudent student
        LEFT JOIN tblBHSHomestay homestay ON student.StudentID = homestay.StudentID
        WHERE student.StudentID=? AND student.Password=? AND student.CurrentStudent='Y'
    ",

    'profile-by-id' => "
        SELECT {$profileFields}
        FROM tblBHSStudent student
        LEFT JOIN tblBHSHomestay homestay ON student.StudentID = homestay.StudentID
        WHERE student.StudentID=? AND student.CurrentStudent='Y'
    ",

    'profile-by-email' => "
        SELECT {$profileFields}
        FROM tblBHSStudent student
        LEFT JOIN tblBHSHomestay homestay ON student.StudentID = homestay.StudentID
        WHERE student.SchoolEmail=? AND student.CurrentStudent='Y'
    ",

    'profile-by-email-with-password' => "
        SELECT {$profileFields}, student.Password password
        FROM tblBHSStudent student
        LEFT JOIN tblBHSHomestay homestay ON student.StudentID = homestay.StudentID
        WHERE student.SchoolEmail=? AND student.CurrentStudent='Y'
    ",

    'profile-by-reset-info' => "
        SELECT {$basicProfileFields}
        FROM tblBHSStudent student
        WHERE student.StudentID=?
          AND student.DOB=?
          AND student.CurrentStudent='Y'
    ",

    'student-password-by-email' => "
        SELECT a.LoginID schoolEmail, a.UserID studentId, a.PW1 password
        FROM tblBHSUserAuth a
        JOIN tblBHSStudent b ON a.UserID = b.StudentID
        WHERE a.LoginID=? AND a.Category='20' AND b.CurrentStudent='Y'
    ",

    'num-terms-by-id' => "
        SELECT COUNT(DISTINCT c.SemesterID) numTerms
        FROM tblBHSStudent a
        JOIN tblBHSStudentSubject b ON a.StudentID = b.StudNum
        JOIN tblBHSSubject c ON b.SubjectID = c.SubjectID
        JOIN tblBHSSemester d ON c.SemesterID = d.SemesterID
        WHERE a.StudentID=? AND a.CurrentStudent = 'Y' AND d.SemesterID<=?
    ",

    'num-aep-terms-by-id' => "
        SELECT COUNT(DISTINCT c.SemesterID) numAEPTerms
        FROM tblBHSStudent a
        JOIN tblBHSStudentSubject b ON a.StudentID = b.StudNum
        JOIN tblBHSSubject c ON b.SubjectID = c.SubjectID
        JOIN tblBHSSemester d ON c.SemesterID = d.SemesterID
        WHERE a.StudentID=? AND a.CurrentStudent = 'Y' AND d.SemesterID<=? AND c.SubjectName LIKE 'AEP%'
    ",

    'num-terms-list' => "
        SELECT a.StudentID, COUNT(DISTINCT c.SemesterID) numTerms
        FROM tblBHSStudent a
        JOIN tblBHSStudentSubject b ON a.StudentID = b.StudNum
        JOIN tblBHSSubject c ON b.SubjectID = c.SubjectID
        JOIN tblBHSSemester d ON c.SemesterID = d.SemesterID
        WHERE a.CurrentStudent='Y' AND d.SemesterID<=?
        GROUP BY a.StudentID
        ORDER BY a.StudentID ASC
    ",

    'student-terms' => "
        SELECT DISTINCT c.SemesterID, d.SemesterName
        FROM tblBHSStudent a
        JOIN tblBHSStudentSubject b ON a.StudentID = b.StudNum
        JOIN tblBHSSubject c ON b.SubjectID = c.SubjectID
        JOIN tblBHSSemester d ON c.SemesterID = d.SemesterID
        WHERE a.StudentID=? AND a.CurrentStudent = 'Y' AND d.SemesterID<=?
        ORDER BY c.SemesterID ASC
    ",

    'absence-list-by-student' => "
        SELECT StudSubjID studentCourseId, SUM(AbsencePeriod) absenceCount, SUM(LatePeriod) lateCount
        FROM tblBHSAttendance
        WHERE StudSubjID IN ({courseList}) AND Excuse = '0'
        GROUP BY StudSubjID
    ",

    'course-list-by-student' => "
        SELECT
            course.SemesterID termId,
            studentCourse.StudSubjID studentCourseId,
            studentCourse.StudNum studentId,
            studentCourse.SubjectID courseId,
            course.SubjectName courseName,
            staff.StaffID teacherId,
            CONCAT(staff.FirstName, ' ', staff.LastName) teacherName,
            staff.FirstName teacherFirstName,
            staff.LastName teacherLastName,
            course.PName provincialName,
            course.CourseCd courseCode,
            course.RoomNo roomNo,
            course.Cap cap,
            course.Spa spa,
            course.Credit credit,
            course.Type courseType
        FROM tblBHSStudentSubject studentCourse
        JOIN tblBHSSubject course ON studentCourse.SubjectID = course.SubjectID
        JOIN tblStaff staff ON course.TeacherID = staff.StaffID
        WHERE course.SemesterID=? AND studentCourse.StudNum=?
        ORDER BY
            course.Credit DESC,
            course.SubjectName ASC
    ",

    'category-list-by-student' => "
        SELECT
            course.SemesterID termId,
            studentCourse.StudSubjID studentCourseId,
            studentCourse.StudNum studentId,
            studentCourse.SubjectID courseId,
            category.CategoryID categoryId,
            category.CategoryCode categoryCode,
            category.Text categoryTitle,
            CONCAT(category.Text, ' (', ROUND(category.CategoryWeight * 100, 2), '%)') categoryLabel,
            category.CategoryWeight categoryWeight,
            CONCAT(ROUND(category.CategoryWeight * 100, 2), '%') categoryWeightLabel
        FROM tblBHSStudentSubject studentCourse
        JOIN tblBHSSubject course ON studentCourse.SubjectID = course.SubjectID
        JOIN tblBHSOGSCourseCategory category ON category.SemesterID = course.SemesterID AND category.SubjectID = course.SubjectID
        WHERE course.SemesterID=? AND studentCourse.StudNum=?
        ORDER BY course.SubjectName ASC, category.CategoryCode ASC, category.Text
    ",

    'item-list-by-student' => "
        SELECT
            course.SemesterID termId,
            studentCourse.StudSubjID studentCourseId,
            studentCourse.StudNum studentId,
            studentCourse.SubjectID courseId,
            item.CategoryID categoryId,
            item.CategoryItemID itemId,
            item.Title itemTitle,
            CONCAT(item.Title, ' (', ROUND(item.ItemWeight * 100, 2), '%)') itemLabel,
            item.ItemWeight itemWeight,
            CONCAT(ROUND(item.ItemWeight * 100, 2), '%') itemWeightLabel,
            item.ScoreType scoreType,
            item.Body description,
            item.{$_maxValueName} maxScore,
            {$_assignDate},
            {$_dueDate}
        FROM tblBHSStudentSubject studentCourse
        JOIN tblBHSSubject course ON studentCourse.SubjectID = course.SubjectID
        JOIN tblBHSOGSCategoryItems item ON item.SemesterID = course.SemesterID AND item.SubjectID = course.SubjectID
        WHERE course.SemesterID=? AND studentCourse.StudNum=? AND item.AssignDate > '1900-01-01'
        ORDER BY item.AssignDate ASC, item.Title ASC
    ",

    'grade-list-by-student' => "
        SELECT
            course.SemesterID termId,
            studentCourse.StudSubjID studentCourseId,
            studentCourse.StudNum studentId,
            studentCourse.SubjectID courseId,
            item.CategoryID categoryId,
            item.CategoryItemID itemId,
            grade.GradeID gradeId,
            item.Title itemTitle,
            item.ItemWeight itemWeight,
            grade.ScorePoint scorePoint,
            grade.ScorePoint / item.{$_maxValueName} scoreRate,
            item.ScoreType scoreType,
            grade.Comment comment,
            grade.Exempted exempted,
            item.{$_maxValueName} maxScore,
            {$_assignDate},
            {$_dueDate},
            CASE WHEN item.AssignDate <= {$_nowFuncName} AND ABS(DATEDIFF(DAY, item.AssignDate, {$_nowFuncName})) > 3 AND grade.ScorePoint IS NULL THEN 1 ELSE 0 END overdue,
            CASE
              WHEN grade.Exempted = 1 THEN 'exempted'
              WHEN grade.ScorePoint IS NOT NULL THEN 'normal'
              WHEN item.AssignDate <= {$_nowFuncName} AND ABS(DATEDIFF(DAY, item.AssignDate, {$_nowFuncName})) > 3 THEN 'overdue'
              ELSE 'pending'
            END gradeStatus
        FROM tblBHSStudentSubject studentCourse
        JOIN tblBHSSubject course ON studentCourse.SubjectID = course.SubjectID
        JOIN tblBHSOGSCategoryItems item ON item.SemesterID = course.SemesterID AND item.SubjectID = course.SubjectID
        LEFT JOIN tblBHSOGSGrades grade ON studentCourse.StudSubjID = grade.StudSubjID AND item.CategoryItemID = grade.CategoryItemID
        WHERE course.SemesterID=? AND studentCourse.StudNum=? AND item.AssignDate > '1900-01-01'
        ORDER BY item.AssignDate ASC, item.Title ASC
    ",

    'item-average-list' => "
        SELECT
            grade.CategoryItemID itemId,
            COUNT(grade.CategoryItemID) itemCount,
            AVG(grade.ScorePoint) averageScore,
            AVG(grade.ScorePoint) / AVG(item.{$_maxValueName}) averageRate
        FROM tblBHSOGSGrades grade
        JOIN tblBHSOGSCategoryItems item ON grade.CategoryItemID = item.CategoryItemID
        JOIN tblBHSOGSCourseCategory category ON item.CategoryID = category.CategoryID
        JOIN tblBHSSubject course ON category.SubjectID = course.SubjectID
        WHERE course.SubjectID IN ({courseList}) AND grade.ScorePoint IS NOT NULL AND grade.Exempted <> 1
        GROUP BY grade.CategoryItemID
    ",

    'course-average-list' => "
        SELECT courseId, COUNT(itemId) itemCount, AVG(averageRate) averageRate
        FROM
        (
          SELECT
            item.SubjectID courseId,
            grade.CategoryItemID itemId,
            AVG(grade.ScorePoint) / AVG(item.{$_maxValueName}) averageRate
          FROM tblBHSOGSGrades grade
            JOIN tblBHSOGSCategoryItems item ON grade.CategoryItemID = item.CategoryItemID
            JOIN tblBHSSubject course ON item.SubjectID = course.SubjectID
          WHERE course.SubjectID IN ({courseList}) AND grade.ScorePoint IS NOT NULL AND grade.Exempted <> 1
          GROUP BY item.SubjectID, grade.CategoryItemID
        ) itemAverage
        GROUP BY courseId
    ",

    'category-grade-list' => "
        SELECT
          student.StudentID studentId,
          course.SubjectID courseId,
          item.CategoryID categoryId,
          COUNT(item.CategoryItemID) itemCount,
          SUM((grade.ScorePoint / item.{$_maxValueName}) * item.ItemWeight) rateOrigin,
          SUM(item.ItemWeight) weightTotal,
          SUM((grade.ScorePoint / item.{$_maxValueName}) * item.ItemWeight) * (1 / SUM(item.ItemWeight)) rateScaled
        FROM tblBHSOGSGrades grade
          JOIN tblBHSOGSCategoryItems item ON grade.CategoryItemID = item.CategoryItemID
          JOIN tblBHSOGSCourseCategory category ON item.CategoryID = category.CategoryID
          JOIN tblBHSSubject course ON category.SubjectID = course.SubjectID
          JOIN tblBHSStudentSubject studentSubject ON grade.StudSubjID = studentSubject.StudSubjID
          JOIN tblBHSStudent student ON studentSubject.StudNum = StudentID
        WHERE grade.SemesterID=? AND student.StudentID=? AND grade.ScorePoint IS NOT NULL AND grade.Exempted <> 1
        GROUP BY student.StudentID, course.SubjectID, item.CategoryID
    ",

    // query for course grade list of student
    'course-grade-list' => "
        SELECT
          studentId,
          courseId,
          COUNT(categoryId) categoryCount,
          SUM(categoryWeight) categoryWeightTotal,
          SUM(categoryRateScaled * categoryWeight) courseRateOrigin,
          SUM(categoryRateScaled * categoryWeight) * (1 / SUM(categoryWeight)) courseRateScaled
        FROM (
          SELECT
            student.StudentID studentId,
            course.SubjectID courseId,
            category.CategoryID categoryId,
            category.CategoryWeight categoryWeight,
            SUM((grade.ScorePoint / item.{$_maxValueName}) * item.ItemWeight) * (1 / SUM(item.ItemWeight)) categoryRateScaled
          FROM tblBHSOGSGrades grade
            JOIN tblBHSOGSCategoryItems item ON grade.CategoryItemID = item.CategoryItemID
            JOIN tblBHSOGSCourseCategory category ON item.CategoryID = category.CategoryID
            JOIN tblBHSSubject course ON category.SubjectID = course.SubjectID
            JOIN tblBHSStudentSubject studentSubject ON grade.StudSubjID = studentSubject.StudSubjID
            JOIN tblBHSStudent student ON studentSubject.StudNum = StudentID
          WHERE grade.SemesterID='{termId}' AND student.StudentID='{studentId}' AND grade.ScorePoint IS NOT NULL AND grade.Exempted <> 1
          GROUP BY student.StudentID, course.SubjectID, category.CategoryID, category.CategoryWeight
        ) categoryGrade
        GROUP BY studentId, courseId
    ",

    'midcut-course-grade-list' => "
        SELECT
          studentId,
          courseId,
          COUNT(categoryId) categoryCount,
          SUM(categoryWeight) categoryWeightTotal,
          SUM(categoryRateScaled * categoryWeight) courseRateOrigin,
          SUM(categoryRateScaled * categoryWeight) * (1 / SUM(categoryWeight)) courseRateScaled
        FROM (
          SELECT
            student.StudentID studentId,
            course.SubjectID courseId,
            category.CategoryID categoryId,
            category.CategoryWeight categoryWeight,
            SUM((grade.ScorePoint / item.{$_maxValueName}) * item.ItemWeight) * (1 / SUM(item.ItemWeight)) categoryRateScaled
          FROM tblBHSOGSGrades grade
            JOIN tblBHSOGSCategoryItems item ON grade.CategoryItemID = item.CategoryItemID
            JOIN tblBHSOGSCourseCategory category ON item.CategoryID = category.CategoryID
            JOIN tblBHSSubject course ON category.SubjectID = course.SubjectID
            JOIN tblBHSStudentSubject studentSubject ON grade.StudSubjID = studentSubject.StudSubjID
            JOIN tblBHSStudent student ON studentSubject.StudNum = StudentID
          WHERE
            grade.SemesterID='{termId}' AND
            student.StudentID='{studentId}' AND
            item.AssignDate <= '{midcutDate}' AND
            grade.ScorePoint IS NOT NULL AND
            grade.Exempted <> 1
          GROUP BY student.StudentID, course.SubjectID, category.CategoryID, category.CategoryWeight
        ) categoryGrade
        GROUP BY studentId, courseId
    ",

    'student-activity-list-v2' => "
        SELECT
             A.StudentActivityID activityId
            ,A.Title title
            ,A.ActivityCategory category
            ,CONVERT(CHAR(10), A.SDate, 126) activityDate
            ,A.Body description
            ,A.ApproverStaffID staffId
            ,D.FirstName firstName
            ,D.LastName lastName
            ,A.Hours hours
            ,A.VLWE qvwh
            ,A.StudentID studentId
            ,A.SemesterID termId
        FROM tblBHSSPStudentActivities A
        LEFT JOIN tblStaff D ON A.ApproverStaffID = D.StaffID
        WHERE A.StudentID='{studentId}' AND A.ActivityStatus = '80' AND A.SDate >= '2018-01-01'
        ORDER BY A.SDate DESC
    ",

    'student-activity-list' => "
        SELECT
          {$studentActivityFields},
          {$activityFields},
          student.FirstName firstName,
          student.LastName lastName,
          student.EnglishName englishName,
          student.SchoolEmail schoolEmail
        FROM tblBHSSPStudentActivities studentActivity
          LEFT JOIN tblBHSSPActivity activity ON activity.ActivityID = studentActivity.ActivityID
          LEFT JOIN tblBHSSPActivityConfig category ON studentActivity.ActivityCategory = category.ActivityCategory
          LEFT JOIN tblBHSStudent student ON studentActivity.StudentID = student.StudentID
          LEFT JOIN tblStaff staff ON activity.StaffID = staff.StaffID
          LEFT JOIN tblStaff staff2 ON activity.StaffID2 = staff2.StaffID
        WHERE studentActivity.StudentID='{studentId}' AND studentActivity.SemesterID='{termId}'
        ORDER BY
          studentActivity.SDate ASC,
          studentActivity.Title ASC
    ",

    'student-activity-by-id' => "
        SELECT
          {$studentActivityFields},
          {$activityFields},
          student.FirstName firstName,
          student.LastName lastName,
          student.EnglishName englishName,
          student.SchoolEmail schoolEmail
        FROM tblBHSSPStudentActivities studentActivity
          LEFT JOIN tblBHSSPActivity activity ON activity.ActivityID = studentActivity.ActivityID
          LEFT JOIN tblBHSSPActivityConfig category ON studentActivity.ActivityCategory = category.ActivityCategory
          LEFT JOIN tblBHSStudent student ON studentActivity.StudentID = student.StudentID
          LEFT JOIN tblStaff staff ON activity.StaffID = staff.StaffID
          LEFT JOIN tblStaff staff2 ON activity.StaffID2 = staff2.StaffID
        WHERE studentActivity.StudentActivityID='{activityId}' AND studentActivity.SemesterID='{termId}'
    ",

    'school-activity-list' => "
        SELECT
          {$activityFields}
        FROM tblBHSSPActivity activity
          LEFT JOIN tblBHSSPActivityConfig category ON activity.ActivityCategory = category.ActivityCategory
          LEFT JOIN tblStaff staff ON activity.StaffID = staff.StaffID
          LEFT JOIN tblStaff staff2 ON activity.StaffID2 = staff2.StaffID
        WHERE activity.SemesterID='{termId}'
        ORDER BY
          activity.StartDate ASC,
          activity.Title ASC
    ",

    'admin-student-activity-list2' => "
        SELECT TOP 4000
          {$studentActivityFields},
          {$activityFields},
          student.FirstName firstName,
          student.LastName lastName,
          student.EnglishName englishName,
          student.SchoolEmail schoolEmail
        FROM tblBHSSPStudentActivities studentActivity
          LEFT JOIN tblBHSSPActivity activity ON activity.ActivityID = studentActivity.ActivityID
          LEFT JOIN tblBHSSPActivityConfig category ON studentActivity.ActivityCategory = category.ActivityCategory
          LEFT JOIN tblBHSStudent student ON studentActivity.StudentID = student.StudentID
          LEFT JOIN tblStaff staff ON activity.StaffID = staff.StaffID
          LEFT JOIN tblStaff staff2 ON activity.StaffID2 = staff2.StaffID
        WHERE studentActivity.SemesterID='{termId}'
        ORDER BY
          activity.StartDate ASC,
          activity.Title ASC,
          student.LastName ASC,
          student.FirstName ASC,
          student.EnglishName ASC
    ",
    'admin-student-activity-list' => "
        SELECT TOP 4000
          {$studentActivityFields},
          {$activityFields},
          student.FirstName firstName,
          student.LastName lastName,
          student.EnglishName englishName,
          student.SchoolEmail schoolEmail
          FROM tblBHSSPStudentActivities studentActivity
                  LEFT JOIN tblBHSSPActivity activity ON activity.ActivityID = studentActivity.ActivityID
                  LEFT JOIN tblBHSSPActivityConfig category ON studentActivity.ActivityCategory = category.ActivityCategory
                  LEFT JOIN tblBHSStudent student ON studentActivity.StudentID = student.StudentID
                  LEFT JOIN tblStaff staff ON activity.StaffID = staff.StaffID
                  LEFT JOIN tblStaff staff2 ON activity.StaffID2 = staff2.StaffID
                WHERE studentActivity.SemesterID='{termId}'
                ORDER BY
                  actualStartDate ASC,
                  studentActivity.Title ASC,
                  student.LastName ASC,
                  student.FirstName ASC,
                  student.EnglishName ASC
    ",

    'school-activity-enrolls' => "
        SELECT
        studentActivity.ActivityID activityId,
        COUNT(studentActivity.StudentID) enrollCount
        FROM tblBHSSPStudentActivities studentActivity
        GROUP BY studentActivity.ActivityID
    ",

    'school-activity-by-id' => "
        SELECT
          {$activityFields}
        FROM tblBHSSPActivity activity
          LEFT JOIN tblBHSSPActivityConfig category ON activity.ActivityCategory = category.ActivityCategory
          LEFT JOIN tblStaff staff ON activity.StaffID = staff.StaffID
          LEFT JOIN tblStaff staff2 ON activity.StaffID2 = staff2.StaffID
        WHERE activity.SemesterID='{termId}' AND activity.ActivityID='{activityId}'
    ",

    'student-activity-count' => "
        SELECT COUNT(*) activityCount
        FROM tblBHSSPStudentActivities
        WHERE StudentID='{studentId}' AND ActivityID='{activityId}'
    ",

    'insert-student-activity' => "
        INSERT INTO tblBHSSPStudentActivities
          (ActivityStatus,StudentID,ActivityID,ActualLocation,ActualSDate,ActualEDate,ActualHours,ActualAllDay,ActualDPA,ActualWorkExp,ActualCommService,ApproverStaffID,ModifyDate,CreateDate)
        VALUES
          ('10',?,?,?,?,?,?,?,?,?,?,NULL,?,?)
    ",

    'update-student-activity' => "
        UPDATE tblBHSSPStudentActivities
        SET ActivityStatus=?
        WHERE SemesterID=? AND StudentActivityID=?
    ",

);
