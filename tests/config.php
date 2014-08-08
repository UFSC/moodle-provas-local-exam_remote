<?php

define('CLI_SCRIPT', true);

include(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/filelib.php');

$domainname = 'https://???';
$token = '???';

function call_ws($functionname, $params) {
    global $domainname, $token;

    header('Content-Type: text/plain');
    $serverurl = $domainname . '/webservice/rest/server.php' .
                                '?wstoken=' . $token .
                                '&wsfunction=' . $functionname .
                                '&moodlewsrestformat=json';
    $curl = new curl;
    $curl->setopt(array('CURLOPT_SSL_VERIFYHOST'=>0, 'CURLOPT_SSL_VERIFYPEER'=>0));
    $respj = $curl->post($serverurl, $params);
    return json_decode($respj);
}
