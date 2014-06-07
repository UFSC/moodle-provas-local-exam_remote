<?php

$functions = array(
        'local_exam_remote_get_courses' => array(
                'classname'   => 'local_exam_remote_external',
                'methodname'  => 'get_courses',
                'classpath'   => 'local/exam_remote/externallib.php',
                'description' => 'Return a list of courses (shortname, fullname) giving a username and role name',
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
        )

);

$services = array(
        'Exam Moodle' => array(
                'functions' => array ('local_exam_remote_get_courses',
                                      'local_exam_remote_get_students',
                                      'local_exam_remote_is_teacher_or_monitor',
                                     ),
                'restrictedusers' => 0,
                'enabled'=>1,
        )
);
