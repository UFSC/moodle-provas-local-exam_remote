<?php

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

class local_exam_remote_external extends external_api {

    static $functions = array('editor'  => array('capability'=>'local/exam_remote:write_exam',   'only_enrolled'=>false),
                              'proctor' => array('capability'=>'local/exam_remote:conduct_exam', 'only_enrolled'=>false),
                              'monitor' => array('capability'=>'local/exam_remote:monitor_exam', 'only_enrolled'=>false),
                              'student' => array('capability'=>'local/exam_remote:take_exam',    'only_enrolled'=>true),
                             );

// --------------------------------------------------------------------------------------------------------

    // Return an array with the user functions (roles) the user have int the local Moodle

    public static function get_user_functions_parameters() {
        return new external_function_parameters(
                        array('username'=>new external_value(PARAM_TEXT, 'Username', VALUE_DEFAULT, ''))
                   );
    }

    public static function get_user_functions($username) {
        global $DB;

        $params = self::validate_parameters(self::get_user_functions_parameters(), array('username'=>$username));

        if(!$userid = $DB->get_field('user', 'id', array('username'=>$username, 'deleted'=>0, 'suspended'=>0))) {
            return array();
        }

        return self::get_local_functions($userid);
    }

    public static function get_user_functions_returns() {
        return new external_multiple_structure(new external_value(PARAM_TEXT, 'User function'));
    }

// --------------------------------------------------------------------------------------------------------

    public static function get_courseid_parameters() {
        return new external_function_parameters(
                        array('key'  =>new external_value(PARAM_TEXT, 'Course key: shortname or idnumber'),
                              'value'=>new external_value(PARAM_TEXT, 'key value'))
                   );
    }

    public static function get_courseid($key, $value) {
        global $DB;

        $params = self::validate_parameters(self::get_courseid_parameters(), array('key'=>$key, 'value'=>$value));

        if(empty($key) || empty($value)) {
            return 0;
        }
        if($key != 'shortname' && $key != 'idnumber') {
            return 0;
        }

        if($DB->count_records('course', array($key=>$value)) > 1) {
            return -1;
        }

        if($id = $DB->get_field('course', 'id', array($key=>$value))) {
            return $id != 1 ? $id : 0;
        } else {
            return 0;
        }
    }

    public static function get_courseid_returns() {
        return new external_value(PARAM_INT, 'Course id, 0 if course does not exist, or -1 if there are more than one course');
    }

// --------------------------------------------------------------------------------------------------------

    public static function get_user_courses_parameters() {
        return new external_function_parameters(
                        array('username'=>new external_value(PARAM_TEXT, 'Username', VALUE_DEFAULT, ''))
                   );
    }

    public static function get_user_courses($username) {
        global $DB;

        $params = self::validate_parameters(self::get_user_courses_parameters(), array('username'=>$username));

        if(!$userid = $DB->get_field('user', 'id', array('username'=>$username, 'deleted'=>0, 'suspended'=>0))) {
            return array();
        }

        return self::get_local_courses($userid);
    }

    public static function get_user_courses_returns() {
        return new external_multiple_structure(
                      new external_single_structure(
                          array(
                              'shortname'  => new external_value(PARAM_TEXT, 'Course shortname'),
                              'fullname'   => new external_value(PARAM_TEXT, 'Course fullname'),
                              'categoryid' => new external_value(PARAM_INT, 'Category id'),
                              'functions'  => new external_multiple_structure(new external_value(PARAM_TEXT), 'User functions'),
                          )
                      )
               );
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
                         array('id'   => new external_value(PARAM_INT, 'Category id'),
                               'name' => new external_value(PARAM_TEXT, 'Category name'),
                               'path' => new external_multiple_structure(new external_value(PARAM_INT), 'Category path'),
                         )
                     )
               );
    }

// --------------------------------------------------------------------------------------------------------

    public static function get_students_parameters() {
        return new external_function_parameters(
                        array('shortname'=>new external_value(PARAM_TEXT, 'Course shortname', VALUE_DEFAULT, ''),
                              'userfields'=>new external_multiple_structure(new external_value(PARAM_TEXT, 'User field shortname'))
                             )
                    );
    }

    public static function get_students($shortname, $userfields) {
        global $DB;

        $params = self::validate_parameters(self::get_students_parameters(),
                                            array('shortname'=>$shortname, 'userfields'=>$userfields));

        $user_table_fields = $DB->get_columns('user');

        $info_fields = array();
        $extra_fields = array();
        foreach($userfields AS $f) {
            if(isset($user_table_fields[$f])) {
                if($f != 'id') {
                    $extra_fields[] = $f;
                }
            } else {
                $info_fields[] = $f;
            }
        }
        $extra_fields = array_unique($extra_fields);

        if(empty($extra_fields)) {
            $user_fields_str = 'u.id';
        } else {
            $user_fields_str = 'u.id, u.' . implode(', u.', $extra_fields);
        }

        $sql = "SELECT DISTINCT {$user_fields_str}
                  FROM {course} c
                  JOIN {context} ctx ON (ctx.instanceid = c.id AND ctx.contextlevel = :contextlevel)
                  JOIN {role} r ON (r.shortname = 'student')
                  JOIN {role_assignments} ra ON (ra.contextid = ctx.id AND ra.roleid = r.id)
                  JOIN {user} u ON (u.id = ra.userid)
                 WHERE c.shortname = :shortname";
        $students = $DB->get_records_sql($sql, array('shortname'=>$shortname, 'contextlevel'=>CONTEXT_COURSE));

        // trata campos extras contidos na tabela 'user'
        foreach($students AS $st) {
            $extras = array();
            foreach($extra_fields AS $f) {
                $obj = new stdClass();
                $obj->shortname = $f;
                $obj->value = $st->$f;
                unset($st->$f);
                $extras[] = $obj;
            }
            $st->userfields = $extras;
        }

        if(!empty($info_fields)) {
            // trata campos extras que estão nos dados adicionais dos usuários
            foreach($students AS $st) {
                profile_load_custom_fields($st);
                foreach($info_fields AS $f) {
                    if(isset($st->profile[$f])) {
                        $obj = new stdClass();
                        $obj->shortname = $f;
                        $obj->value = $st->profile[$f];
                        $st->userfields[] = $obj;
                    }
                }
                unset($st->profile);
            }
        }

        return $students;
    }

    public static function get_students_returns() {
        return new external_multiple_structure(
                    new external_single_structure(
                        array('id'  => new external_value(PARAM_TEXT, 'User id'),
                              'userfields' => new external_multiple_structure(
                                     new external_single_structure(array('shortname' => new external_value(PARAM_TEXT, 'shortname'),
                                                                         'value'     => new external_value(PARAM_TEXT, 'value'))))
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

    // --------------------------------------------------------------------------------------------------------

    // Auxiliary functions

    public static function get_local_courses($userid, $functions=array()) {
        $functions = empty($functions) ? array_keys(self::$functions) : $functions;
        $courses = array();

        list($sql, $params) = self::get_sql_over_courses($userid, $functions, 'c.shortname, c.fullname, c.category as categoryid');
        self::get_courses_from_sql($userid, $sql, $params, $courses);

        foreach($functions AS $i=>$f) {
            if(self::$functions[$f]['only_enrolled']) {
                unset($functions[$i]);
            }
        }

        if(empty($functions)) {
            return $courses;
        }

        list($sql, $params) = self::get_sql_over_categories($userid, $functions, 'c.shortname, c.fullname, c.category as categoryid');
        self::get_courses_from_sql($userid, $sql, $params, $courses);

        return $courses;
    }

    public static function get_local_functions($userid) {
        $functions = array_keys(self::$functions);

        list($sql, $params) = self::get_sql_over_courses($userid, $functions);
        $functions1 = self::get_functions_from_sql($userid, $sql, $params);

        foreach(self::$functions AS $f=>$p) {
            if($p['only_enrolled']) {
                unset($functions[$f]);
            }
        }

        list($sql, $params) = self::get_sql_over_categories($userid, $functions);
        $functions2 = self::get_functions_from_sql($userid, $sql, $params);

        return array_unique( array_merge($functions1, $functions2) );
    }

    private function get_functions_from_sql($userid, $sql, $params) {
        global $DB;

        $capabilities = array();
        foreach(self::$functions AS $f=>$p) {
            $capabilities[$p['capability']] = $f;
        }

        $functions = array();
        foreach($DB->get_recordset_sql($sql, $params) AS $c) {
            $context = context_course::instance($c->id);
            if (has_capability($c->capability, $context, $userid)) {
                $functions[$capabilities[$c->capability]] = true;
            }
        }

        return array_keys($functions);
    }

    private function get_courses_from_sql($userid, $sql, $params, &$courses) {
        global $DB;

        $capabilities = array();
        foreach(self::$functions AS $f=>$p) {
            $capabilities[$p['capability']] = $f;
        }

        foreach($DB->get_recordset_sql($sql, $params) AS $c) {
            $context = context_course::instance($c->id);
            if (has_capability($c->capability, $context, $userid)) {
                if(isset($courses[$c->id])) {
                    $func = $capabilities[$c->capability];
                    if(!in_array($func, $courses[$c->id]->functions)) {
                        $courses[$c->id]->functions[] = $func;
                    }
                } else {
                    $c->functions = array($capabilities[$c->capability]);
                    $id = $c->id;
                    unset($c->capability);
                    unset($c->id);
                    $courses[$id] = $c;
                }
            }
        }

        return $courses;
    }

    private static function get_sql_over_courses($userid, $functions, $extra_fields='') {
        global $DB;

        $capabilities = array();
        foreach($functions AS $f) {
            $capabilities[] = self::$functions[$f]['capability'];
        }

        $extra_fields = empty($extra_fields) ? '' : ', ' . $extra_fields;
        list($in_sql, $params) = $DB->get_in_or_equal($capabilities, SQL_PARAMS_NAMED);

        $sql = "SELECT DISTINCT c.id, rc.capability {$extra_fields}
                  FROM {role_assignments} ra
                  JOIN {context} ctx ON (ctx.id = ra.contextid AND ctx.contextlevel = :contextlevel)
                  JOIN {role_capabilities} rc ON (rc.roleid = ra.roleid AND (rc.contextid = 1 OR rc.contextid = ra.contextid))
                  JOIN {course} c ON (c.id = ctx.instanceid AND c.visible = 1)
                 WHERE ra.userid = :userid
                   AND rc.capability {$in_sql}
                   AND rc.permission = 1";
        $params['userid'] = $userid;
        $params['contextlevel'] = CONTEXT_COURSE;

        return array($sql, $params);
    }

    private static function get_sql_over_categories($userid, $functions, $extra_fields='') {
        global $DB;

        $capabilities = array();
        foreach($functions AS $f) {
            $capabilities[] = self::$functions[$f]['capability'];
        }

        $extra_fields = empty($extra_fields) ? '' : ', ' . $extra_fields;
        list($in_sql1, $params1) = $DB->get_in_or_equal($capabilities, SQL_PARAMS_NAMED);
        list($in_sql2, $params2) = $DB->get_in_or_equal($capabilities, SQL_PARAMS_NAMED);
        $params = array_merge($params1, $params2);

        $sql = "SELECT c.id, rc.capability {$extra_fields}
                  FROM {role_assignments} ra
                  JOIN {context} ctx ON (ctx.id = ra.contextid AND ctx.contextlevel = :contextlevel1)
                  JOIN {course_categories} cc ON (cc.id = ctx.instanceid AND cc.visible = 1)
                  JOIN {role_capabilities} rc ON (rc.roleid = ra.roleid AND (rc.contextid = 1 OR rc.contextid = ctx.id))
                  JOIN {course_categories} ccc ON (ccc.depth >= cc.depth AND (ccc.id = cc.id OR ccc.path LIKE CONCAT('%/',cc.id, '/%')) AND ccc.visible = 1)
                  JOIN {course} c ON (c.category = ccc.id AND c.visible = 1)
                 WHERE ra.userid = :userid1
                   AND rc.capability {$in_sql1}
                   AND rc.permission = 1

                 UNION

                SELECT c.id, rc.capability {$extra_fields}
                  FROM {role_assignments} ra
                  JOIN {context} ctx ON (ctx.id = ra.contextid AND ctx.contextlevel = :contextlevel2)
                  JOIN {course_categories} cc ON (cc.id = ctx.instanceid AND cc.visible = 1)
                  JOIN {course_categories} ccsub ON (ccsub.path LIKE CONCAT('%/',cc.id, '/%') AND ccsub.visible = 1)
                  JOIN {context} ctxsub ON (ctxsub.contextlevel = ctx.contextlevel AND ctxsub.instanceid = ccsub.id)
                  JOIN {role_capabilities} rc ON (rc.roleid = ra.roleid AND rc.contextid = ctxsub.id)
                  JOIN {course_categories} ccc ON (ccc.depth >= ccsub.depth AND (ccc.id = ccsub.id OR ccc.path LIKE CONCAT('%/',ccsub.id, '/%')) AND ccc.visible = 1)
                  JOIN {course} c ON (c.category = ccc.id AND c.visible = 1)
                 WHERE ra.userid = :userid2
                   AND rc.capability {$in_sql2}
                   AND rc.permission = 1";
        $params['userid1'] = $userid;
        $params['userid2'] = $userid;
        $params['contextlevel1'] = CONTEXT_COURSECAT;
        $params['contextlevel2'] = CONTEXT_COURSECAT;

        return array($sql, $params);
    }
}
