<?php

$functions = array(
        'local_exam_remote_get_courses' => array(
                'classname'   => 'local_exam_remote_external',
                'methodname'  => 'get_courses',
                'classpath'   => 'local/exam_remote/externallib.php',
                'description' => 'Return a list of courses (shortname, fullname) giving a username and role name',
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

        'local_exam_remote_is_teacher_or_monitor' => array(
                'classname'   => 'local_exam_remote_external',
                'methodname'  => 'is_teacher_or_monitor',
                'classpath'   => 'local/exam_remote/externallib.php',
                'description' => 'Verify if the user is a teacher or monitor',
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
                'functions' => array ('local_exam_remote_get_courses',
                                      'local_exam_remote_get_categories',
                                      'local_exam_remote_get_students',
                                      'local_exam_remote_is_teacher_or_monitor',
                                      'local_exam_remote_restore_activity',
                                     ),
                'restrictedusers' => 1,
                'enabled'=>1,
        )
);
