<?php

use rester\sql\cfg;
use rester\sql\rester;

$response_body = array(
    'success'=>false,
    'msg'=>[],
    'error'=>[],
    'warning'=>[],
    'data'=>''
);


try
{
    require_once './rester/common.php';
    cfg::init();
    $response_body['data'] = rester::run();
    $response_body['msg'] = rester::msg();
    $response_body['error'] = rester::error();
    $response_body['warning'] = rester::warning();
    if(rester::isSuccess()) $response_body['success'] = true;
}
catch (Exception $e)
{
    $response_body['error']['msg'] = $e->getMessage();
    $response_body['error']['trace'] = explode("\n",$e->getTraceAsString());
}

// print response code & response header
rester::run_headers();

echo json_encode($response_body);

