<?php

function get_courses_by_capability($username, $capability, $only_visible=true, $only_enrolled=true) {
    global $DB;

    if(!$user = $DB->get_record('user', array('username'=>$username))) {
        return array();
    }

    if($only_enrolled) {
        $candidate_courses = enrol_get_all_users_courses($user->id, true, 'id, shortname, fullname, visible', 'fullname');
    } else {
        $candidate_courses = enrol_get_all_users_courses($user->id, true, 'id, shortname, fullname, visible', 'fullname');
        // todo: add other courses
    }

    $courses = array();
    foreach ($candidate_courses as $id=>$course) {
        if ($course->visible || !$only_visible) {
            if ($context = context_course::instance($id)) {
                if (has_capability($capability, $context, $user->id)) {
                    $courses[] = array('shortname'=>$course->shortname, 'fullname'=>$course->fullname);
                }
            }
        }
    }
    return $courses;
}
