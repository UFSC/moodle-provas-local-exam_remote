<?php

include(dirname(__FILE__) . '/config.php');

$functionname = 'local_exam_remote_get_user_courses';
$params = array('username'=>'49429248987');

$ret = call_ws($functionname, $params);
var_dump($ret);
