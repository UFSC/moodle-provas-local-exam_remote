<?php

include(dirname(__FILE__) . '/config.php');

$functionname = 'local_exam_remote_has_exam_capability';
$params = array('username'=>'80132022');

$ret = call_ws($functionname, $params);
var_dump($ret);
