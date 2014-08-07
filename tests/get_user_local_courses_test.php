<?php

define('CLI_SCRIPT', true);

include(dirname(__FILE__) . '/../../../config.php');
require_once(dirname(__FILE__) . '/../externallib.php');

$courses = local_exam_remote_external::get_local_courses(3);

var_dump($courses);
