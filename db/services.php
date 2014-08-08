<?php

$functions = array(
        'local_exam_remote_get_user_courses' => array(
                'classname'   => 'local_exam_remote_external',
                'methodname'  => 'get_user_courses',
                'classpath'   => 'local/exam_remote/externallib.php',
                'description' => 'Return a list of user courses (shortname, fullname) giving a username',
                'type'        => 'read',
        ),

        'local_exam_remote_get_user_functions' => array(
                'classname'   => 'local_exam_remote_external',
                'methodname'  => 'get_user_functions',
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
);

$services = array(
       'Moodle Exam' => array(
                'functions' => array ('local_exam_remote_get_user_courses',
                                      'local_exam_remote_get_user_functions',
                                      'local_exam_remote_get_categories',
                                      'local_exam_remote_get_students',
                                      'local_exam_remote_restore_activity',

                                      'core_group_get_course_groupings',
                                      'core_group_get_course_groups',
                                      'core_group_get_group_members',
                                      'core_group_get_groupings',
                                      'core_user_get_users_by_field',
                                     ),
                'restrictedusers' => 1,
                'enabled'=>1,
        )
);
