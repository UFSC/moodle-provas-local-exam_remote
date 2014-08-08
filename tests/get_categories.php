<?php

include(dirname(__FILE__) . '/config.php');

$functionname = 'local_exam_remote_get_categories';
$params = array('categoryids[0]'=>25);

$ret = call_ws($functionname, $params);
var_dump($ret);
