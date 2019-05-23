<?php
define('__RESTER__', TRUE);

global /** @var rester $current_rester */
$current_rester;

// -----------------------------------------------------------------------------
/// include classes
// -----------------------------------------------------------------------------
require_once dirname(__FILE__) . '/core/cfg.class.php';
require_once dirname(__FILE__) . '/core/session.class.php';
require_once dirname(__FILE__) . '/core/rester_response.class.php';
require_once dirname(__FILE__) . '/core/rester_config.class.php';
require_once dirname(__FILE__) . '/core/rester_verify.class.php';
require_once dirname(__FILE__) . '/core/rester.class.php';
require_once dirname(__FILE__) . '/db.class.php';
require_once dirname(__FILE__) . '/resterSQL.class.php';
require_once dirname(__FILE__) . '/core/common.lib.php';

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
