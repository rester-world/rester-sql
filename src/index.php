<?php

use rester\sql\cfg;
use rester\sql\rester;

$response_body = array(
    'success'=>false,
    'msg'=>'',
    'error'=>[],
    'warning'=>[],
    'data'=>''
);


try
{
    require_once './rester/common.php';
    cfg::init();
    $response_body['data'] = rester::run();
    $response_body['msg'] = implode(',', rester::msg());
    $response_body['warning'] = rester::warning();
    if(rester::isSuccess()) $response_body['success'] = true;
}
catch (Exception $e)
{
    $response_body['msg'] = $e->getMessage();
}

// print response code & response header
rester::run_headers();

echo json_encode($response_body);

