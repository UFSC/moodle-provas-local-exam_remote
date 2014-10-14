<?php
define('CLI_SCRIPT', true);

require_once('../../../config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/webservice/lib.php');

$username = 'wsprovas';
$rolename = 'wsprovas';

$webservice           = 'rest';
$service              = 'Moodle Exam';
$token_ip_restriction = '150.162.1.64/26';

$context = context_system::instance();

// ----------------------------------------------------------------------------------
// Cria usuário para webservice

if(!$user = $DB->get_record('user', array('username'=>$username))) {
    $user = new stdclass();
    $user->username  = $username;
    $user->firstname = 'Webservice';
    $user->lastname  = 'Provas';
    $user->email     = 'wsprovas@moodle.ufsc.br';
    $user->password  = 'Qt*;' . rand(100000, 999999);
    $user->auth      = 'manual';
    $user->confirmed = 1;
    $user->policyagreed = 1;
    $user->mnethostid   = $CFG->mnet_localhost_id;
    $user->id = user_create_user($user, false);
}

// ----------------------------------------------------------------------------------
// Cria papel para webservice

if(!$roleid = $DB->get_field('role', 'id', array('shortname'=>$rolename))) {
    $roleid = create_role('Webservice Provas', $rolename, 'Webservice Provas');
    set_role_contextlevels($roleid, array(CONTEXT_SYSTEM));
}
assign_capability('webservice/rest:use', CAP_ALLOW, $roleid, $context->id);
assign_capability('moodle/course:managegroups', CAP_ALLOW, $roleid, $context->id);
assign_capability('moodle/course:view', CAP_ALLOW, $roleid, $context->id);
assign_capability('moodle/restore:restoreactivity', CAP_ALLOW, $roleid, $context->id);

// ----------------------------------------------------------------------------------
// Atribui papel ao usuário no contexto global

role_assign($roleid, $user->id, $context->id);

// ----------------------------------------------------------------------------------
// Habilita o uso de webservices

$configs = array(
                 array('enablewebservices', true),
                );

foreach($configs AS $cfg) {
    if(count($cfg) == 2) {
        set_config($cfg[0], $cfg[1]);
    } else {
        set_config($cfg[0], $cfg[1], $cfg[2]);
    }
}

// ----------------------------------------------------------------------------------
// Ativa protocolo

$active_webservices = empty($CFG->webserviceprotocols) ? array() : explode(',', $CFG->webserviceprotocols);
if(!in_array($webservice, $active_webservices)) {
    $active_webservices[] = $webservice;
    $active_webservices = array_unique($active_webservices);
    set_config('webserviceprotocols', implode(',', $active_webservices));
}

// ----------------------------------------------------------------------------------
// Adicionando usuário ao serviço

if(!$ext_service = $DB->get_record('external_services', array('name'=>$service), 'id, name')) {
    echo "*** NÃO LOCALIZADO SERVIÇO: {$service}\n";
    exit;
}

$webservicemanager = new webservice();
$users = $webservicemanager->get_ws_authorised_users($ext_service->id);
if(!isset($users[$user->id])) {
    $serviceuser = new stdClass();
    $serviceuser->externalserviceid = $ext_service->id;
    $serviceuser->userid = $user->id;
    $webservicemanager->add_ws_authorised_user($serviceuser);
}

// ----------------------------------------------------------------------------------
// Gerando token

if($token = $DB->get_record('external_tokens', array('userid'=>$user->id, 'externalserviceid'=>$ext_service->id))) {
    echo "\nTOKEN previamente criado = {$token->token}\n\n";
} else {
    $token = external_generate_token(EXTERNAL_TOKEN_PERMANENT, $ext_service->id, $user->id, $context, 0, $token_ip_restriction);
    $newtoken = new stdclass();
    $newtoken->id = $DB->get_field('external_tokens', 'id', array('token'=>$token));
    $newtoken->creatorid = 2;
    $DB->update_record('external_tokens', $newtoken);

    echo "\nNOVO TOKEN = {$token}\n\n";
}
