<?php use rester\sql\session;

if(!defined('__RESTER__')) exit;

$session_id = session::id();
return array(
    'title'=>'Passed token check!',
    'session_id'=>$session_id,
);
