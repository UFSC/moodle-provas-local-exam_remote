<?php

define('CLI_SCRIPT', true);

include(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/filelib.php');

$domainname = 'https://mariani.moodle.ufsc.br/presencial2';
$token = 'c0fcac9376624cdca2397b604dcca198';

$functionname = 'local_exam_remote_is_teacher_or_monitor';
$params = array('username'=>'49429248987');

header('Content-Type: text/plain');
$serverurl = $domainname . '/webservice/rest/server.php' .
                            '?wstoken=' . $token .
                            '&wsfunction=' . $functionname .
                            '&moodlewsrestformat=json';
$curl = new curl;
$curl->setopt(array('CURLOPT_SSL_VERIFYHOST'=>0, 'CURLOPT_SSL_VERIFYPEER'=>0));
$respj = $curl->post($serverurl, $params);
$resp = json_decode($respj);
var_dump($resp);
