<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
//
// Este bloco é parte do Moodle Provas - http://tutoriais.moodle.ufsc.br/provas/
// Este projeto é financiado pela
// UAB - Universidade Aberta do Brasil (http://www.uab.capes.gov.br/)
// e é distribuído sob os termos da "GNU General Public License",
// como publicada pela "Free Software Foundation".

/**
 * Plugin webservices.
 *
 * @package    local_exam_remote
 * @author     Antonio Carlos Mariani
 * @copyright  2010 onwards Universidade Federal de Santa Catarina (http://www.ufsc.br)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

class local_exam_remote_external extends external_api {

    static $capabilities = array('local/exam_remote:write_exam'   => 'editor',
                                 'local/exam_remote:conduct_exam' => 'proctor',
                                 'local/exam_remote:monitor_exam' => 'monitor',
                                );

// --------------------------------------------------------------------------------------------------------

    /**
     * Describes the parameters for has_exam_capability.
     *
     * @return external_external_function_parameters
     */
    public static function has_exam_capability_parameters() {
        return new external_function_parameters(
                        array('username'=>new external_value(PARAM_TEXT, 'Username'))
                   );
    }

    /**
     * Returns true if the user has one of the self::$capabilities over any visible course. Returns false otherwise.
     *
     * @param String $username
     * @return boolean
     */
    public static function has_exam_capability($username) {
        global $DB;

        $params = self::validate_parameters(self::has_exam_capability_parameters(), array('username'=>$username));

        if(!$userid = $DB->get_field('user', 'id', array('username'=>$username, 'deleted'=>0, 'suspended'=>0))) {
            return false;
        }

        return self::has_local_exam_capability($userid);
    }

    /**
     * Describes the has_exam_capability return value.
     *
     * @return external_value
     */
    public static function has_exam_capability_returns() {
        return new external_value(PARAM_BOOL);
    }

// --------------------------------------------------------------------------------------------------------

    /**
     * Describes the parameters for get_courseid.
     *
     * @return external_external_function_parameters
     */
    public static function get_courseid_parameters() {
        return new external_function_parameters(
                        array('key'  =>new external_value(PARAM_TEXT, 'Course key: shortname or idnumber'),
                              'value'=>new external_value(PARAM_TEXT, 'key value'))
                   );
    }

    /**
     * Returns the course id given ists shortname or idnumber. Returns -1 if there are more then one course with the given pair $key/value.
     * Returns 0 the there isn't such course.
     *
     * @param String $username
     * @return int
     */
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

    /**
     * Describes the get_courseid return value.
     *
     * @return external_value
     */
    public static function get_courseid_returns() {
        return new external_value(PARAM_INT, 'Course id, 0 if course does not exist, or -1 if there are more than one course');
    }

// --------------------------------------------------------------------------------------------------------

    /**
     * Describes the parameters for get_user_courses.
     *
     * @return external_external_function_parameters
     */
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

    /**
     * Describes the get_user_courses return value.
     *
     * @return external_multiple_structure
     */
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

    /**
     * Describes the parameters for get_categories.
     *
     * @return external_external_function_parameters
     */
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
        $sql = "SELECT DISTINCT cc2.id, cc2.name, cc2.path
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

    /**
     * Describes the get_categories_returns return value.
     *
     * @return external_multiple_structure
     */
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

    /**
     * Describes the parameters for get_students.
     *
     * @return external_external_function_parameters
     */
    public static function get_students_parameters() {
        return new external_function_parameters(
                        array('shortname'=>new external_value(PARAM_TEXT, 'Course shortname', VALUE_DEFAULT, ''),
                              'userfields'=>new external_multiple_structure(new external_value(PARAM_TEXT, 'User field shortname'),
                                                                            'Array of user fields', VALUE_DEFAULT, array())
                             )
                    );
    }

    public static function get_students($shortname, $userfields = array()) {
        global $DB, $CFG;

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

        if(!$courseid = $DB->get_field('course', 'id', array('shortname'=>$shortname))) {
            return array();
        }

        $context = context_course::instance($courseid);
        list($sql, $params) = get_enrolled_sql($context, null, null, true);
        list($sql_role, $params_role) = $DB->get_in_or_equal(explode(',', $CFG->gradebookroles), SQL_PARAMS_NAMED);
        $params = array_merge($params, $params_role);

        $sql = "SELECT DISTINCT {$user_fields_str}
                  FROM {course} c
                  JOIN {context} ctx ON (ctx.instanceid = c.id AND ctx.contextlevel = :contextlevel)
                  JOIN {role_assignments} ra ON (ra.contextid = ctx.id AND ra.roleid {$sql_role})
                  JOIN {user} u ON (u.id = ra.userid)
                  JOIN ($sql) j ON (j.id = u.id)
                 WHERE c.id = :courseid";
        $params['courseid'] = $courseid;
        $params['contextlevel'] = CONTEXT_COURSE;
        $params['roleids'] = $CFG->gradebookroles;

        $students = $DB->get_records_sql($sql, $params);

        // trata campos extras contidos na tabela 'user'
        foreach($students AS $st) {
            $extras = array();
            foreach($extra_fields AS $f) {
                $obj = new stdClass();
                $obj->field = $f;
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
                        $obj->field = $f;
                        $obj->value = $st->profile[$f];
                        $st->userfields[] = $obj;
                    }
                }
                unset($st->profile);
            }
        }

        return $students;
    }

    /**
     * Describes the get_students return value.
     *
     * @return external_multiple_structure
     */
    public static function get_students_returns() {
        return new external_multiple_structure(
                    new external_single_structure(
                        array('id'  => new external_value(PARAM_TEXT, 'User id'),
                              'userfields' => new external_multiple_structure(
                                     new external_single_structure(array('field' => new external_value(PARAM_TEXT, 'Field name'),
                                                                         'value' => new external_value(PARAM_TEXT, 'Field value'))))
                        )
                    )
        );
    }

// --------------------------------------------------------------------------------------------------------

    /**
     * Describes the parameters for restore_activity.
     *
     * @return external_external_function_parameters
     */
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

        $tmp_backup_dir = $CFG->dataroot . '/temp/backup';
        check_dir_exists($tmp_backup_dir);

        $rand_backup_path = 'activity_restore_' . date('YmdHis') . '_' . rand();
        $fp = get_file_packer('application/vnd.moodle.backup');

        $extracted = $fp->extract_to_pathname($_FILES['backup_file']['tmp_name'], $tmp_backup_dir.'/'.$rand_backup_path);
        if (!$extracted) {
            throw new backup_helper_exception('missing_moodle_backup_file', $rand_backup_path);
        }

        $adminid = $DB->get_field('user', 'id', array('username'=>'admin'));
        $controller = new restore_controller($rand_backup_path, $course->id, backup::INTERACTIVE_NO, backup::MODE_GENERAL,
                                             $adminid, backup::TARGET_EXISTING_ADDING);
        $controller->execute_precheck();
        $controller->execute_plan();

        return 'OK';

    }

    /**
     * Describes the restore_activity return value.
     *
     * @return external_value
     */
    public static function restore_activity_returns() {
        return new external_value(PARAM_TEXT, 'Resultado da restauração. OK se a restauração for realizada com sucesso');
    }

    // --------------------------------------------------------------------------------------------------------

    // Auxiliary functions

    public static function get_local_students($shortname, $userfields = array()) {
        global $DB, $CFG;

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

        if(!$courseid = $DB->get_field('course', 'id', array('shortname'=>$shortname))) {
            return array();
        }

        $context = context_course::instance($courseid);
        list($sql, $params) = get_enrolled_sql($context, null, null, true);
        list($sql_role, $params_role) = $DB->get_in_or_equal(explode(',', $CFG->gradebookroles), SQL_PARAMS_NAMED);
        $params = array_merge($params, $params_role);

        $sql = "SELECT DISTINCT {$user_fields_str}
                  FROM {course} c
                  JOIN {context} ctx ON (ctx.instanceid = c.id AND ctx.contextlevel = :contextlevel)
                  JOIN {role_assignments} ra ON (ra.contextid = ctx.id AND ra.roleid {$sql_role})
                  JOIN {user} u ON (u.id = ra.userid)
                  JOIN ($sql) j ON (j.id = u.id)
                 WHERE c.id = :courseid";
        $params['courseid'] = $courseid;
        $params['contextlevel'] = CONTEXT_COURSE;
        $params['roleids'] = $CFG->gradebookroles;

        $students = $DB->get_records_sql($sql, $params);

        // trata campos extras contidos na tabela 'user'
        foreach($students AS $st) {
            $extras = array();
            foreach($extra_fields AS $f) {
                $obj = new stdClass();
                $obj->field = $f;
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
                        $obj->field = $f;
                        $obj->value = $st->profile[$f];
                        $st->userfields[] = $obj;
                    }
                }
                unset($st->profile);
            }
        }

        return $students;
    }

    public static function get_local_courses($userid) {
        global $DB, $CFG;

        $courses = array();
        $course_fields = 'c.shortname, c.fullname, c.category as categoryid';

        list($sql, $params) = self::get_sql_over_courses($userid, $course_fields);
        self::get_courses_from_sql($userid, $sql, $params, $courses);

        list($sql, $params) = self::get_sql_over_categories($userid, $course_fields);
        self::get_courses_from_sql($userid, $sql, $params, $courses);

        list($sql, $params) = $DB->get_in_or_equal(explode(',', $CFG->gradebookroles), SQL_PARAMS_NAMED);
        $sql = "SELECT DISTINCT ctx.instanceid as courseid, ctx.instanceid
                  FROM {role_assignments} ra
                  JOIN {context} ctx ON (ctx.id = ra.contextid AND ctx.contextlevel = :contextcourselevel)
                 WHERE ra.userid = :userid
                   AND ra.roleid {$sql}";
        $params['userid'] = $userid;
        $params['contextcourselevel'] = CONTEXT_COURSE;
        $ras = $DB->get_records_sql($sql, $params);

        foreach(enrol_get_all_users_courses($userid, true) AS $c) {
            if($c->visible && isset($ras[$c->id])) {
                if(isset($courses[$c->id])) {
                    if(!in_array('student', $courses[$c->id]->functions)) {
                        $courses[$c->id]->functions[] = 'student';
                    }
                } else {
                    $course = new stdclass();
                    $course->shortname = $c->shortname;
                    $course->fullname = $c->fullname;
                    $course->categoryid = $c->category;
                    $course->functions = array('student');
                    $courses[$c->id] = $course;
                }
            }
        }

        return $courses;
    }

    public static function has_local_exam_capability($userid) {
        // Iterates over the course where the user is enrolled
        list($sql, $params) = self::get_sql_over_courses($userid);
        if(self::has_exam_capability_from_sql($userid, $sql, $params)) {
            return true;
        }

        // Iterates over the course under the categories where the user has some role
        list($sql, $params) = self::get_sql_over_categories($userid);
        return self::has_exam_capability_from_sql($userid, $sql, $params);
    }

    private function has_exam_capability_from_sql($userid, $sql, $params) {
        global $DB;

        foreach($DB->get_recordset_sql($sql, $params) AS $c) {
            $context = context_course::instance($c->id);
            if (has_capability($c->capability, $context, $userid)) {
                return true;
            }
        }

        return false;
    }

    private static function get_courses_from_sql($userid, $sql, $params, &$courses) {
        global $DB;

        foreach($DB->get_recordset_sql($sql, $params) AS $c) {
            $context = context_course::instance($c->id);
            if (has_capability($c->capability, $context, $userid)) {
                if(isset($courses[$c->id])) {
                    $func = self::$capabilities[$c->capability];
                    if(!in_array($func, $courses[$c->id]->functions)) {
                        $courses[$c->id]->functions[] = $func;
                    }
                } else {
                    $c->functions = array(self::$capabilities[$c->capability]);
                    $id = $c->id;
                    unset($c->capability);
                    unset($c->id);
                    $courses[$id] = $c;
                }
            }
        }

        return $courses;
    }

    /**
     * Retorna código sql para obtenção dos cursos visíveis (e correspondentes capabilities) nos quais o usuário possui alguma atribuição explícita
     * de papel que possui uma ou mais das 'capabilities' relacionadas a provas. As capabilities podem ter sido definidas globalmente no papel
     * ou atribuidas localmente no curso.
     *
     * @param int $userid
     * @param String $course_extra_fields
     * @return String sql code
     */
    private static function get_sql_over_courses($userid, $course_extra_fields='') {
        global $DB;

        $course_extra_fields = empty($course_extra_fields) ? '' : ', ' . $course_extra_fields;

        list($cap_sql1, $params1) = $DB->get_in_or_equal(array_keys(self::$capabilities), SQL_PARAMS_NAMED);
        list($cap_sql2, $params2) = $DB->get_in_or_equal(array_keys(self::$capabilities), SQL_PARAMS_NAMED);
        $params = array_merge($params1, $params2);

        $sql = "SELECT c.id, rc.capability {$course_extra_fields}
                  FROM {role_assignments} ra
                  JOIN {context} ctx ON (ctx.id = ra.contextid AND ctx.contextlevel = :contextcourselevel1)
                  JOIN {role_capabilities} rc ON (rc.roleid = ra.roleid AND (rc.contextid = 1 OR rc.contextid = ra.contextid))
                  JOIN {course} c ON (c.id = ctx.instanceid AND c.visible = 1)
                 WHERE ra.userid = :userid1
                   AND rc.capability {$cap_sql1}
                   AND rc.permission = 1

                 UNION

                SELECT c.id, rc.capability {$course_extra_fields}
                  FROM {role_assignments} ra
                  JOIN {context} ctx ON (ctx.id = ra.contextid AND ctx.contextlevel = :contextcourselevel2)
                  JOIN {course} c ON (c.id = ctx.instanceid AND c.visible = 1)
                  JOIN {course_categories} cc ON (cc.id = c.category AND cc.visible = 1)
                  JOIN {context} ctxc ON (ctxc.instanceid = cc.id AND ctxc.contextlevel = :contextcoursecatlevel1)
                  JOIN {context} ctxs ON (ctxs.id = ctxc.id OR (ctxs.contextlevel = :contextcoursecatlevel2 AND ctxs.depth < ctxc.depth AND ctxc.path LIKE CONCAT(ctxs.path, '/%')))
                  JOIN {role_capabilities} rc ON (rc.roleid = ra.roleid AND rc.contextid > 1 AND rc.contextid = ctxs.id)
                 WHERE ra.userid = :userid2
                   AND rc.capability {$cap_sql2}
                   AND rc.permission = 1";

        $params['userid1'] = $userid;
        $params['userid2'] = $userid;
        $params['contextcourselevel1'] = CONTEXT_COURSE;
        $params['contextcourselevel2'] = CONTEXT_COURSE;
        $params['contextcoursecatlevel1'] = CONTEXT_COURSECAT;
        $params['contextcoursecatlevel2'] = CONTEXT_COURSECAT;

        return array($sql, $params);
    }

    private static function get_sql_over_categories($userid, $course_extra_fields='') {
        global $DB;

        $course_extra_fields = empty($course_extra_fields) ? '' : ', ' . $course_extra_fields;

        list($cap_sql1, $params1) = $DB->get_in_or_equal(array_keys(self::$capabilities), SQL_PARAMS_NAMED);
        list($cap_sql2, $params2) = $DB->get_in_or_equal(array_keys(self::$capabilities), SQL_PARAMS_NAMED);
        $params = array_merge($params1, $params2);

        $sql = "SELECT c.id, rc.capability {$course_extra_fields}
                  FROM {role_assignments} ra
                  JOIN {context} ctx ON (ctx.id = ra.contextid AND ctx.contextlevel = :contextcatlevel1)
                  JOIN {context} ctxs ON (ctxs.id = ctx.id OR (ctxs.contextlevel = :contextcatlevel2 AND ctxs.depth > ctx.depth AND ctxs.path LIKE CONCAT(ctx.path, '/%')))
                  JOIN {role_capabilities} rc ON (rc.roleid = ra.roleid)
                  JOIN {course_categories} cc ON (cc.id = ctxs.instanceid AND cc.visible)
                  JOIN {course} c ON (c.category = cc.id AND c.visible)
                 WHERE ra.userid = :userid1
                   AND rc.capability {$cap_sql1}
                   AND rc.contextid = 1
                   AND rc.permission = 1

                 UNION

                SELECT c.id, rcc.capability {$course_extra_fields}
                  FROM {role_assignments} ra
                  JOIN {context} ctx ON (ctx.id = ra.contextid AND ctx.contextlevel = :contextcatlevel3)
                  JOIN {context} ctxs ON (ctxs.id = ctx.id OR (ctxs.contextlevel = :contextcatlevel4 AND ctxs.path LIKE CONCAT(ctx.path, '/%')))
                  JOIN (SELECT rc.capability, rc.roleid, ctxs.id as contextid
                          FROM {role_capabilities} rc
                          JOIN {context} ctx ON (ctx.id = rc.contextid AND ctx.contextlevel = :contextcatlevel5)
                          JOIN {context} ctxs ON (ctxs.id = ctx.id OR (ctxs.contextlevel = :contextcatlevel6 AND ctxs.depth > ctx.depth AND ctxs.path LIKE CONCAT(ctx.path, '/%')))
                         WHERE rc.capability {$cap_sql2}
                           AND rc.permission = 1
                           AND rc.contextid > 1) rcc
                    ON (rcc.contextid = ctxs.id AND rcc.roleid = ra.roleid)
                  JOIN {course_categories} cc ON (cc.id = ctxs.instanceid AND cc.visible)
                  JOIN {course} c ON (c.category = cc.id AND c.visible)
                 WHERE ra.userid = :userid2";

        $params['userid1'] = $userid;
        $params['userid2'] = $userid;
        $params['contextcatlevel1'] = CONTEXT_COURSECAT;
        $params['contextcatlevel2'] = CONTEXT_COURSECAT;
        $params['contextcatlevel3'] = CONTEXT_COURSECAT;
        $params['contextcatlevel4'] = CONTEXT_COURSECAT;
        $params['contextcatlevel5'] = CONTEXT_COURSECAT;
        $params['contextcatlevel6'] = CONTEXT_COURSECAT;

        return array($sql, $params);
    }
}
