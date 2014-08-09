<?php

include(dirname(__FILE__) . '/config.php');

$functionname = 'local_exam_remote_get_students';
// $params = array('shortname'=>'CCN9114-0107132 (20132)', 'userfields[0]'=>'kkk', 'userfields[1]'=>'username', 'userfields[2]'=>'firstname');
$params = array('shortname'=>'CCN9114-0107132 (20132)', 'userfields[0]'=>'username');

$ret = call_ws($functionname, $params);
var_dump($ret);
