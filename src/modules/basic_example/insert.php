<?php
if(!defined('__RESTER__')) exit;

use rester\sql\rester;

$key = rester::param('key');
$value = rester::param('value');
$old = array_pop(rester::call_proc('fetch_by_key',[':key'=>$key]));

if($old['no'])
{
    rester::failure();
    rester::msg('Already exists key!');
    return false;
}

rester::msg('Success!');
return rester::call_proc('direct_insert',[':key'=>$key,':value'=>$value]);
