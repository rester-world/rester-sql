<?php
namespace rester\sql;
use Exception;
use PDO;
use Redis;

/**
 * Class rester
 * kevinpark@webace.co.kr
 *
 * 기본 핵심 모듈
 */
class rester
{
    const path_module = 'modules';
    const file_config = 'config.ini';

    protected static $request_param = [];
    protected static $response_body = null;
    protected static $response_code = 200;

    protected static $success = true;
    protected static $msg = [];
    protected static $warning = [];
    protected static $error = [];

    protected static $cfg = [];
    protected static $check_auth = false;
    protected static $use_cache = false;
    protected static $cache_timeout;

    /**
     * execute header();
     */
    public static function run_headers()
    {
        // 응답 결과 코드 설정
        http_response_code(self::$response_code);
        header("Content-type: application/json; charset=UTF-8");
    }

    /**
     * run rester
     *
     * @throws Exception
     */
    public static function run()
    {
        $module = cfg::module();
        $proc = cfg::proc();

        //=====================================================================
        /// include config.ini
        /// Must included first!
        /// Use another function.
        //=====================================================================
        $cfg = array();
        if($path = self::path_cfg())
        {
            $cfg = parse_ini_file($path,true, INI_SCANNER_TYPED);
        }
        self::$cfg = $cfg;

        //=====================================================================
        /// check cache option and auth option
        //=====================================================================
        if(self::cfg('auth',cfg::proc())) self::$check_auth = true;
        if(self::cfg('cache',cfg::proc()))
        {
            self::$use_cache = true;
            self::$cache_timeout = self::cfg('cache',cfg::proc());
        }

        //=====================================================================
        /// include verify function
        //=====================================================================
        if($path_verify_func = self::path_verify_func())
        {
            include $path_verify_func;
        }

        //=====================================================================
        /// check request parameter
        /// check body | query string
        //=====================================================================
        if($path_verify = self::path_verify())
        {
            $schema = new schema($path_verify);
            try
            {
                if($data = $schema->validate(cfg::parameter()))
                    foreach($data as $k => $v) rester::set_request_param($k, $v);
            }
            catch (Exception $e)
            {
                throw new Exception("request-body | query: ".$e->getMessage());
            }
        }


        //=====================================================================
        /// check files
        //=====================================================================
        $path_sql = self::path_sql();
        $path_proc = self::path_proc();
        if(false === $path_proc && false === $path_sql)
        {
            throw new Exception("Not found procedure. Module: {$module}, Procedure: {$proc} ");
        }


        //=====================================================================
        /// check auth
        //=====================================================================
        if(self::$check_auth) { session::get(cfg::token()); }

        //=====================================================================
        /// check cache
        //=====================================================================
        $redis_cfg = cfg::cache();
        if(self::$use_cache && !($redis_cfg['host'] && $redis_cfg['port']))
            throw new Exception("Require cache config to use cache.");

        $response_data = null;
        $redis = new Redis();
        $cache_key = implode('_', array_merge(array($module,$proc),self::param()));
        if(self::$use_cache)
        {
            $redis->connect($redis_cfg['host'], $redis_cfg['port']);
            if($redis_cfg['auth']) $redis->auth($redis_cfg['auth']);
            // get cached data
            $response_data = json_decode($redis->get($cache_key),true);
        }

        //=====================================================================
        /// include procedure
        //=====================================================================
        if(!$response_data)
        {
            if($path_sql)
            {
                $pdo = db::get();
                $query = file_get_contents($path_sql);
                $response_data = [];
                foreach($pdo->query($query,PDO::FETCH_ASSOC) as $row)
                {
                    $response_data[] = $row;
                }
            }
            elseif($path_proc)
            {
                $response_data = include $path_proc;
            }
        }

        // cached body
        if(self::$use_cache && !$redis->get($cache_key)) { $redis->set($cache_key,json_encode($response_data),self::$cache_timeout); }

        // close redis
        if(self::$use_cache) { $redis->close(); }

        // 저장된 $body 출력
        return $response_data;
    }

    /**
     * @param string $module
     * @param string $proc
     * @param array  $query
     *
     * @return mixed
     */
    public static function call_module($module, $proc, $query=[])
    {
        $old_module = cfg::change_module($module);
        $old_proc = cfg::change_proc($proc);

        $res = false;
        if($path = self::path_proc())
        {
            $res = include $path;
        }
        else
        {
            self::failure();
            self::msg("Can not found module: {$module}");
        }

        cfg::change_proc($old_proc);
        cfg::change_module($old_module);
        return $res;
    }

    /**
     * @param string $proc
     * @param array  $query
     *
     * @return mixed
     */
    public static function call_proc($proc, $query=[])
    {
        $old_proc = cfg::change_proc($proc);

        $res = false;
        if($path = self::path_proc())
        {
            $res = include $path;
        }
        else
        {
            self::failure();
            self::msg("Can not found procedure: {$proc}");
        }

        cfg::change_proc($old_proc);
        return $res;
    }


    /**
     * @param string $proc
     * @param array $query
     * @return string|bool
     */
    public static function url_proc($proc, $query=[])
    {
        if(!$proc) return false;
        $http_host = cfg::Get('default','http_host');
        $module = cfg::module();
        $_query = [];
        foreach ($query as $k=>$v) { $_query[] = $k.'='.$v; }
        $_query = trim(implode('&',$_query));
        $_query = $_query?'?'.$_query:'';
        return  $http_host."/v1/{$module}/{$proc}{$_query}";
    }

    /**
     * @param string $module
     * @param string $proc
     * @param array $query
     * @return bool|string
     */
    public static function url_module($module, $proc, $query=[])
    {
        if(!$module || !$proc) return false;
        $http_host = cfg::Get('default','http_host');
        $_query = [];
        foreach ($query as $k=>$v) { $_query[] = $k.'='.$v; }
        $_query = trim(implode('&',$_query));
        $_query = $_query?'?'.$_query:'';
        return  $http_host."/v1/{$module}/{$proc}{$_query}";
    }

    /**
     * Path module
     *
     * @return string
     */
    protected static function path_module() { return dirname(__FILE__).'/../'.self::path_module; }

    /**
     * Path to procedure file
     *
     * @return bool|string
     */
    protected static function path_proc()
    {
        $path = implode('/',array(
            self::path_module(),
            cfg::module(),
            cfg::proc().'.php'
        ));

        if(is_file($path)) return $path;
        return false;
    }

    /**
     * Path to procedure file
     *
     * @return bool|string
     * @throws Exception
     */
    protected static function path_sql()
    {
        $path = implode('/',array(
            self::path_module(),
            cfg::module(),
            cfg::proc().'.sql'
        ));

        if(is_file($path)) return $path;
        return false;
    }

    /**
     * Path to config file
     *
     * @return bool|string
     * @throws Exception
     */
    public static function path_cfg()
    {
        $path = implode('/',array(
            self::path_module(),
            cfg::module(),
            self::file_config
        ));

        if(is_file($path)) return $path;
        return false;
    }

    /**
     * Path to verify file
     *
     * @return bool|string
     * @throws Exception
     */
    protected static function path_verify()
    {
        $module_name = cfg::module();
        $proc_name = cfg::proc();

        $path = implode('/',array(
            self::path_module(),
            $module_name,
            $proc_name.'.ini'
        ));

        if(is_file($path)) return $path;
        return false;
    }

    /**
     * Path to verify file
     *
     * @return bool|string
     * @throws Exception
     */
    protected static function path_verify_func()
    {
        $module_name = cfg::module();
        $proc_name = cfg::proc();

        $path = implode('/',array(
            self::path_module(),
            $module_name,
            $proc_name.'.verify.php'
        ));

        if(is_file($path)) return $path;
        return false;
    }

    /**
     * set request body
     *
     * @param string $key
     * @param string $value
     */
    public static function set_request_param($key, $value) { self::$request_param[$key] = $value; }

    /**
     * return analyzed parameter
     *
     * @param null|string $key
     * @return bool|mixed
     */
    public static function param($key=null)
    {
        if(isset(self::$request_param[$key])) return self::$request_param[$key];
        if($key == null) return self::$request_param;
        return false;
    }

    /**
     * @param string $section
     * @param string $key
     *
     * @return string|array
     */
    public static function cfg($section='', $key='')
    {
        if($section==='') return self::$cfg;
        if($section && $key) return self::$cfg[$section][$key];
        return self::$cfg[$section];
    }

    /**
     * @param integer $code
     */
    public static function set_response_code($code) { self::$response_code = $code; }

    /**
     * Add message
     *
     * @param null|string $msg
     *
     * @return array
     */
    public static function msg($msg=null)
    {
        if($msg===null) return self::$msg;
        else self::$msg[] = $msg;
        return null;
    }

    /**
     * Add warning message
     *
     * @param null|string $msg
     *
     * @return array
     */
    public static function warning($msg=null)
    {
        if($msg===null) return self::$warning;
        else self::$warning[] = $msg;
        return null;
    }

    /**
     * set failure
     */
    public static function failure() { self::$success = false; }

    /**
     * @return bool
     */
    public static function isSuccess() { return self::$success; }
}
