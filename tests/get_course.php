<?php

include(dirname(__FILE__) . '/config.php');

$functionname = 'local_exam_remote_get_courseid';

$params = array('key'=>'shortname', 'value'=>'INE5413-04208 (20141)');
$ret = call_ws($functionname, $params);
var_dump($ret);

$params = array('key'=>'shortname', 'value'=>'INE5413-04208 (20141) zz');
$ret = call_ws($functionname, $params);
var_dump($ret);

$params = array('key'=>'idnumber', 'value'=>' ');
$ret = call_ws($functionname, $params);
var_dump($ret);

$params = array('key'=>'idnumber', 'value'=>'abc');
$ret = call_ws($functionname, $params);
var_dump($ret);
