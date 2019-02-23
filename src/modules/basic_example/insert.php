<?php if(!defined('__RESTER__')) exit;

use rester\sql\rester_response;

$key = request_param('key');
$value = request_param('value');
$old = array_pop(request_procedure('fetch_by_key',[':key'=>$key]));

if($old['no'])
{
    rester_response::error("Already exists key! [{$old['key']}]");
    return false;
}

rester_response::msg('Success!');
return request_procedure('direct_insert',[':key'=>$key,':value'=>$value]);
