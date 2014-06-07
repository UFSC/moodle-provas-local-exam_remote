<?php

require_once($CFG->libdir . "/externallib.php");

class local_exam_remote_external extends external_api {

    static $roles = array('teacher'=>array('capability'=>'local/exam_remote:write_exam',   'only_visible'=>false, 'only_enrolled'=>true),
                          'monitor'=>array('capability'=>'local/exam_remote:monitor_exam', 'only_visible'=>true,  'only_enrolled'=>true),
                          'student'=>array('capability'=>'local/exam_remote:take_exam',    'only_visible'=>true,  'only_enrolled'=>true));

// --------------------------------------------------------------------------------------------------------

    public static function get_courses_parameters() {
        return new external_function_parameters(
                        array('username'=>new external_value(PARAM_TEXT, 'Username', VALUE_DEFAULT, ''),
                              'rolename'=>new external_value(PARAM_TEXT, 'Rolename', VALUE_DEFAULT, '')
                             )
                   );
    }

    public static function get_courses($username, $rolename) {
        global $DB;

        $params = self::validate_parameters(self::get_courses_parameters(), array('username'=>$username, 'rolename'=>$rolename));

        if(!isset(self::$roles[$rolename])) {
            return array();
        }

        if(!$userid = $DB->get_field('user', 'id', array('username'=>$username, 'deleted'=>0, 'suspended'=>0))) {
            return array();
        }

        return self::get_courses_by_capability($userid, self::$roles[$rolename]['capability'],
                        self::$roles[$rolename]['only_visible'], self::$roles[$rolename]['only_enrolled']);
    }

    public static function get_courses_returns() {
        return new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'shortname' => new external_value(PARAM_RAW, 'Course shortname'),
                            'fullname'  => new external_value(PARAM_RAW, 'Course fullname'),
                        )
                    )
        );
    }

    private static function get_courses_by_capability($userid, $capability, $only_visible=true, $only_enrolled=true) {
        $candidate_courses = enrol_get_all_users_courses($userid, true, 'id, shortname, fullname, visible', 'fullname');
        if(!$only_enrolled) {
            // todo: add other courses
        }

        $courses = array();
        foreach ($candidate_courses as $id=>$course) {
            if ($course->visible || !$only_visible) {
                if ($context = context_course::instance($id)) {
                    if (has_capability($capability, $context, $userid)) {
                        $courses[] = array('shortname'=>$course->shortname, 'fullname'=>$course->fullname);
                    }
                }
            }
        }
        return $courses;
    }

// --------------------------------------------------------------------------------------------------------

    public static function is_teacher_or_monitor_parameters() {
        return new external_function_parameters(
                        array('username'=>new external_value(PARAM_TEXT, 'Username', VALUE_DEFAULT, ''))
                   );
    }

    public static function is_teacher_or_monitor($username) {
        global $DB;

        $params = self::validate_parameters(self::is_teacher_or_monitor_parameters(), array('username'=>$username));

        if(!$userid = $DB->get_field('user', 'id', array('username'=>$username, 'deleted'=>0, 'suspended'=>0))) {
            return false;
        }

        foreach(array('monitor', 'teacher') AS $rolename) {
            if(self::has_role($userid, self::$roles[$rolename]['capability'],
                            self::$roles[$rolename]['only_visible'], self::$roles[$rolename]['only_enrolled'])) {
                return true;
            }
        }
        return false;
    }

    public static function is_teacher_or_monitor_returns() {
        return new external_value(PARAM_BOOL);
    }

    private static function has_role($userid, $capability, $only_visible=true, $only_enrolled=true) {
        $candidate_courses = enrol_get_all_users_courses($userid, true, 'id, visible');
        foreach ($candidate_courses as $id=>$course) {
            if ($course->visible || !$only_visible) {
                if ($context = context_course::instance($id)) {
                    if (has_capability($capability, $context, $userid)) {
                        return true;
                    }
                }
            }
        }

        if(!$only_enrolled) {
            // todo: test other courses
        }

        return false;
    }
// --------------------------------------------------------------------------------------------------------

    public static function get_students_parameters() {
        return new external_function_parameters(
                        array('shortname'=>new external_value(PARAM_TEXT, 'Course shortname', VALUE_DEFAULT, ''))
                    );
    }

    public static function get_students($shortname) {
        global $DB;

        $params = self::validate_parameters(self::get_students_parameters(), array('shortname'=>$shortname));
        $sql = "SELECT DISTINCT u.username, u.idnumber, u.firstname, u.lastname, u.email, u.auth, u.password
                  FROM {course} c
                  JOIN {context} ctx ON (ctx.instanceid = c.id AND ctx.contextlevel = :contextlevel)
                  JOIN {role} r ON (r.shortname = 'student')
                  JOIN {role_assignments} ra ON (ra.contextid = ctx.id AND ra.roleid = r.id)
                  JOIN {user} u ON (u.id = ra.userid)
                 WHERE c.shortname = :shortname";
        return $DB->get_records_sql($sql, array('shortname'=>$shortname, 'contextlevel'=>CONTEXT_COURSE));
    }

    public static function get_students_returns() {
        return new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'username'  => new external_value(PARAM_TEXT, 'username'),
                            'idnumber'  => new external_value(PARAM_TEXT, 'idnumber'),
                            'firstname' => new external_value(PARAM_TEXT, 'firstname'),
                            'lastname'  => new external_value(PARAM_TEXT, 'lastname'),
                            'email'     => new external_value(PARAM_TEXT, 'email'),
                            'auth'      => new external_value(PARAM_TEXT, 'auth'),
                            'password'  => new external_value(PARAM_TEXT, 'password'),
                        )
                    )
        );
    }
}
