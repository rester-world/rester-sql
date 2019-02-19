<?php use rester\sql\rester;
use rester\sql\session;

if(!defined('__RESTER__')) exit;

$id = rester::param('session_id');
$token = session::set($id);

return array(
    'session_id'=>$id,
    'token'=>$token
);
