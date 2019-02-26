<?php
use rester\sql\session;

if(!defined('__RESTER__')) exit;

$id = request_param('session_id');
$token = session::set($id);

return array(
    'session_id'=>$id,
    'token'=>$token
);
