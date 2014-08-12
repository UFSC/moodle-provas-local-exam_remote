<?php

$functions = array(
        'local_exam_remote_get_user_courses' => array(
                'classname'   => 'local_exam_remote_external',
                'methodname'  => 'get_user_courses',
                'classpath'   => 'local/exam_remote/externallib.php',
                'description' => 'Return a list of user courses (shortname, fullname) giving a username',
                'type'        => 'read',
        ),

        'local_exam_remote_has_exam_capability' => array(
                'classname'   => 'local_exam_remote_external',
                'methodname'  => 'has_exam_capability',
                'classpath'   => 'local/exam_remote/externallib.php',
                'description' => 'Return a list of user functions giving a username',
                'type'        => 'read',
        ),

        'local_exam_remote_get_categories' => array(
                'classname'   => 'local_exam_remote_external',
                'methodname'  => 'get_categories',
                'classpath'   => 'local/exam_remote/externallib.php',
                'description' => 'Return a list of categories giving a list of category ids',
                'type'        => 'read',
        ),

        'local_exam_remote_get_students' => array(
                'classname'   => 'local_exam_remote_external',
                'methodname'  => 'get_students',
                'classpath'   => 'local/exam_remote/externallib.php',
                'description' => 'Return a list of students (username, idnumber, auth, firstname, shortname, email, password) enrolled in a course',
                'type'        => 'read',
        ),

        'local_exam_remote_restore_activity' => array(
                'classname'   => 'local_exam_remote_external',
                'methodname'  => 'restore_activity',
                'classpath'   => 'local/exam_remote/externallib.php',
                'description' => 'Restore an activity',
                'type'        => 'write',
        ),

        'local_exam_remote_get_courseid' => array(
                'classname'   => 'local_exam_remote_external',
                'methodname'  => 'get_courseid',
                'classpath'   => 'local/exam_remote/externallib.php',
                'description' => 'Get course id based on shortname ou idnumber',
                'type'        => 'read',
        ),
);

$services = array(
       'Moodle Exam' => array(
                'functions' => array ('local_exam_remote_get_user_courses',
                                      'local_exam_remote_has_exam_capability',
                                      'local_exam_remote_get_categories',
                                      'local_exam_remote_get_students',
                                      'local_exam_remote_get_courseid',
                                      'local_exam_remote_restore_activity',

                                      'core_group_get_course_groupings',
                                      'core_group_get_course_groups',
                                      'core_group_get_group_members',
                                      'core_group_get_groupings',
                                     ),
                'restrictedusers' => 1,
                'enabled'=>1,
        )
);
