<?php if(!defined('__RESTER__')) exit;

$queries = stripslashes(request_param('query'));
$request_params = request_param('params');

// 데이터베이스 접속 결정
$pdo = db::get();

$response_data = [];
foreach(explode(';',$queries) as $query)
{
    $stmt = $pdo->prepare($query,[PDO::ATTR_CURSOR, PDO::CURSOR_FWDONLY]);

    // 필터링 된 파라미터를 받아옴
    // 영문숫자_-로 조합된 키워드 추출
    $params = [];
    preg_match_all('/:[a-zA-z0-9_-]+/', $query, $matches);
    $matches = $matches[0];

    foreach($matches as $bind_param)
    {
        foreach ($request_params as $k=>$v)
        {
            if(strpos($k,':')!==0) $k = ':'.$k;
            if($bind_param==$k)
            {
                $params[$bind_param] = $v;
            }
        }
        if(!isset($params[$bind_param]))
            rester_response::error("There is no parameter for bind. [{$bind_param}]");
    }

    $stmt->execute($params);
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach($res as $row)
    {
        $response_data[] = $row;
    }

}

return $response_data;
