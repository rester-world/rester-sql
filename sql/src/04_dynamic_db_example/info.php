<?php
if(!defined('__RESTER__')) exit;

rester_response::msg("You can use it privately.");
return [
    'type'=>'mysql',
    'host'=>'db2.rester.kr',
    'port'=>'3306',
    'user'=>'rester-sql2',
    'password'=>'rester-sql2',
    'database'=>'rester-sql2',
];
