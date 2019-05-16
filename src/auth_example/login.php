<?php if(!defined('__RESTER__')) exit;

$id = request_param('session_id');
$token = session::set_token($id);

return array(
    'session_id'=>$id,
    'token'=>$token
);
