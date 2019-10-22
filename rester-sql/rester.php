<?php
define('__RESTER__', TRUE);

global /** @var rester $current_rester */
$current_rester;

// -----------------------------------------------------------------------------
/// include classes
// -----------------------------------------------------------------------------
require_once dirname(__FILE__) . '/../rester-core/common.php';
require_once dirname(__FILE__) . '/db.class.php';
require_once dirname(__FILE__) . '/resterSQL.class.php';

// -----------------------------------------------------------------------------
/// include aws
// -----------------------------------------------------------------------------
$path_aws = dirname(__FILE__) . '/../exten_lib/aws/vendor/autoload.php';
if(is_file($path_aws)) {
    require_once $path_aws;
}

/**
 * @param string $name
 * @param string $queries
 * @param array $request_params
 *
 * @return array|bool
 */
function query_database($name, $queries, $request_params)
{
    $response_data = false;
    if($pdo = db::get($name))
    {
        $response_data = [];

        // 여러개의 쿼리 실행
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
    }

    return $response_data;
}

/**
 * @param string $name
 * @param string $queries
 * @param array $request_params
 *
 * @return array|bool
 */
function fetch_database($name, $queries, $request_params)
{
    $response_data = query_database($name, $queries, $request_params);
    if(is_array($response_data)) $response_data = $response_data[0];
    return $response_data;
}

/**
 * @param string $module
 * @param string $proc
 * @param array  $query
 *
 * @return mixed
 */
function request_module($module, $proc, $query=[])
{
    global $current_rester;
    $old_rester = $current_rester;
    $res = false;

    try
    {
        if($token = request_param('token')) $query['token'] = $token;
        if($secret = request_param('secret')) $query['secret'] = $secret;

        $current_rester = new resterSQL($module, $proc, $query);
        $res = $current_rester->run($old_rester);
    }
    catch (Exception $e)
    {
        rester_response::error($e->getMessage());
    }

    $current_rester = $old_rester;
    return $res;
}

/**
 * @param string $proc
 * @param array  $query
 *
 * @return mixed
 */
function request_procedure($proc, $query=[])
{
    global $current_rester;
    $old_rester = $current_rester;
    $res = false;

    try
    {
        if($token = request_param('token')) $query['token'] = $token;
        if($secret = request_param('secret')) $query['secret'] = $secret;

        $current_rester = new resterSQL($current_rester->module(), $proc, $query);
        $res = $current_rester->run($old_rester);
    }
    catch (Exception $e)
    {
        rester_response::error($e->getMessage());
    }

    $current_rester = $old_rester;
    return $res;
}

try
{
    // config init
    cfg::init();

    // 오류출력설정
    if (cfg::debug_mode())
        error_reporting(E_ALL ^ (E_NOTICE | E_STRICT | E_WARNING | E_DEPRECATED));
    else
        error_reporting(0);

    // timezone 설정
    date_default_timezone_set(cfg::timezone());

    $rester = new resterSQL(cfg::module(), cfg::proc(), cfg::request_body());
    $rester->set_public_access();
    $current_rester = $rester;
    rester_response::body($rester->run());
}
catch (Exception $e)
{
    rester_response::failed(sprintf("%02s",$e->getCode()),$e->getMessage());
    rester_response::error_trace(explode("\n",$e->getTraceAsString()));
}
rester_response::run();
