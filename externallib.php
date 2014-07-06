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
                              'shortname'  => new external_value(PARAM_TEXT, 'Course shortname'),
                              'fullname'   => new external_value(PARAM_TEXT, 'Course fullname'),
                              'categoryid' => new external_value(PARAM_INT, 'Category id'),
                          )
                      )
               );
    }

    private static function get_courses_by_capability($userid, $capability, $only_visible=true, $only_enrolled=true) {
        global $DB;

        $candidate_courses = enrol_get_all_users_courses($userid, true, 'id, category, shortname, fullname, visible', 'fullname');
        if(!$only_enrolled) {
            // todo: add other courses
        }

        $courses = array();
        foreach ($candidate_courses as $id=>$course) {
            if ($course->visible || !$only_visible) {
                if ($context = context_course::instance($id)) {
                    if (has_capability($capability, $context, $userid)) {
                        $courses[] = array('shortname'  => $course->shortname,
                                           'fullname'   => $course->fullname,
                                           'categoryid' => $course->category);
                    }
                }
            }
        }

        return $courses;
    }

// --------------------------------------------------------------------------------------------------------

    public static function get_categories_parameters() {
        return new external_function_parameters(
                        array('categoryids'=>new external_multiple_structure(new external_value(PARAM_INT, 'Category id')))
                   );
    }

    public static function get_categories($categoryids) {
        global $DB;

        $params = self::validate_parameters(self::get_categories_parameters(), array('categoryids'=>$categoryids));

        if(empty($categoryids)) {
            return array();
        }

        $str_categoryids = implode(',', $categoryids);
        $sql = "SELECT cc2.id, cc2.name, cc2.path
                  FROM {course_categories} cc
                  JOIN {course_categories} cc2 ON (cc2.id = cc.id OR cc.path LIKE CONCAT('%/',cc2.id,'/%') )
                 WHERE cc.id IN ({$str_categoryids})
              ORDER BY cc2.depth, cc2.name";
        $cats = $DB->get_records_sql($sql);
        foreach($cats AS $catid=>$cat) {
            $path = explode('/', $cat->path);
            unset($path[0]);
            $cats[$catid]->path = $path;
        }

        return array_values($cats);
    }

    public static function get_categories_returns() {
        return new external_multiple_structure(
                     new external_single_structure(
                         array(
                             'id'   => new external_value(PARAM_INT, 'Category id'),
                             'name' => new external_value(PARAM_TEXT, 'Category name'),
                             'path' => new external_multiple_structure(new external_value(PARAM_INT), 'Category path'),
                         )
                     )
               );
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

// --------------------------------------------------------------------------------------------------------

    public static function restore_activity_parameters() {
        return new external_function_parameters(
                array('shortname' => new external_value(PARAM_TEXT, 'Shortname da turma', VALUE_DEFAULT, ''),
                      'username' => new external_value(PARAM_TEXT, 'Username do usuário', VALUE_DEFAULT, ''),
                     )
        );
    }

    public static function restore_activity($shortname='', $username='') {
        global $CFG, $USER, $DB;

        $params = self::validate_parameters(self::restore_activity_parameters(),
                        array('shortname' => $shortname, 'username' => $username));

        $context = get_context_instance(CONTEXT_USER, $USER->id);
        self::validate_context($context);

        if(!$course = $DB->get_record('course', array('shortname'=>$shortname))) {
            return get_string('unknown_course', 'local_exam_remote', $shortname);
        }
        if(!$user = $DB->get_record('user', array('username'=>$username))) {
            return get_string('unknown_user', 'local_exam_remote', $username);
        }

        $context = get_context_instance(CONTEXT_COURSE, $course->id);
        if (!has_capability('moodle/restore:restoreactivity', $context, $user)) {
            return get_string('no_permission', 'local_exam_remote', 'moodle/restore:restoreactivity');
        }

        if(!isset($_FILES['backup_file']) || !isset($_FILES['backup_file']['tmp_name'])) {
            return get_string('backup_file_not_found', 'local_exam_remote');
        }

        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

        check_dir_exists($CFG->dataroot . '/temp/backup');
        $rand_backup_path = 'activity_restore_' . date('YmdHis') . '_' . rand();
        $zip = new zip_packer();
        $zip->extract_to_pathname($_FILES['backup_file']['tmp_name'],  $CFG->dataroot . '/temp/backup/' . $rand_backup_path);

        $adminid = $DB->get_field('user', 'id', array('username'=>'admin'));
        $controller = new restore_controller($rand_backup_path, $course->id, backup::INTERACTIVE_NO, backup::MODE_GENERAL,
                                             $adminid, backup::TARGET_EXISTING_ADDING);
        $controller->execute_precheck();
        $controller->execute_plan();

        return 'OK';

    }

    public static function restore_activity_returns() {
        return new external_value(PARAM_TEXT, 'Resultado da restauração. OK se a restauração for realizada com sucesso');
    }

}
