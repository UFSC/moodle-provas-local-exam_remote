<?php

include(dirname(__FILE__) . '/config.php');

$functionname = 'local_exam_remote_get_students';
$params = array('shortname'=>'INE5413-04208 (20141)', 'extrauserfields[0]'=>'kkk', 'extrauserfields[1]'=>'curso', 'extrauserfields[2]'=>'nomecurso');

$ret = call_ws($functionname, $params);
var_dump($ret);
