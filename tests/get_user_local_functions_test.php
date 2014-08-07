<?php

define('CLI_SCRIPT', true);

include(dirname(__FILE__) . '/../../../config.php');
require_once(dirname(__FILE__) . '/../externallib.php');

$functions = local_exam_remote_external::get_local_functions(3);

var_dump($functions);
