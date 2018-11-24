<?php
/**
 * Class rester
 * kevinpark@webace.co.kr
 *
 * 기본 핵심 모듈
 */
class rester
{
    const path_module = 'modules';
    const file_verify_func = 'verify.php';
    const file_verify = 'verify.ini';
    const file_config = 'config.ini';

    protected static $request_param = array();
    protected static $response_body = null;
    protected static $response_code = 200;

    protected static $success = true;
    protected static $msg = array();

    protected static $cfg;
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
        $method = cfg::request_method();

        ///=====================================================================
        /// include verify function
        ///=====================================================================
        if($path_verify_func = self::path_verify_func())
        {
            include $path_verify_func;
        }

        ///=====================================================================
        /// check request parameter
        /// check body | query string
        ///=====================================================================
        if($path_verify = self::path_verify())
        {
            $schema = new Schema($path_verify);

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

        ///=====================================================================
        /// check file
        /// check auth,cache
        ///=====================================================================
        $path_proc = self::path_proc();
        if(false === $path_proc)
        {
            throw new Exception("Not found procedure. Module: {$module}, Procedure: {$proc} ");
        }

        ///=====================================================================
        /// check auth
        ///=====================================================================
        if(self::$check_auth) { session::get(cfg::token()); }

        ///=====================================================================
        /// check cache
        ///=====================================================================
        $redis_cfg = cfg::cache();
        if(self::$use_cache && !($redis_cfg['host'] && $redis_cfg['port'])) throw new Exception("Require cache config to use cache.");

        $response_data = null;
        $redis = new Redis();
        $cache_key = implode('_', array_merge(array($module,$proc,$method),self::param()));
        if(self::$use_cache)
        {
            $redis->connect($redis_cfg['host'], $redis_cfg['port']);
            if($redis_cfg['auth']) $redis->auth($redis_cfg['auth']);
            // get cached data
            $response_data = json_decode($redis->get($cache_key),true);
        }

        ///=====================================================================
        /// include config.ini
        ///=====================================================================
        $cfg = array();
        if($path = self::path_cfg())
        {
            $cfg = parse_ini_file($path,true, INI_SCANNER_TYPED);
        }
        self::$cfg = $cfg;

        ///=====================================================================
        /// include procedure
        ///=====================================================================
        if(!$response_data) { $response_data = include $path_proc; }

        // cached body
        if(self::$use_cache && !$redis->get($cache_key)) { $redis->set($cache_key,json_encode($response_data),self::$cache_timeout); }

        // close redis
        if(self::$use_cache) { $redis->close(); }

        // 저장된 $body 출력
        return $response_data;
    }

    /**
     * Path to module
     *
     * @return string
     */
    protected static function path_module() { return dirname(__FILE__).'/../../'.self::path_module; }

    /**
     * Path to procedure file
     *
     * @return bool|string
     * @throws Exception
     */
    protected static function path_proc()
    {
        if($timeout = intval(cfg::Get('cache','timeout'))) self::$cache_timeout = $timeout;
        $module_name = cfg::module();
        $proc_name = cfg::proc();

        $method = strtolower(cfg::request_method());
        $path_array = array(
            self::path_module(),
            $module_name,
            $proc_name
        );

        $path = false;
        foreach (glob(implode('/',$path_array).'/'.$method.'*.php') as $filename)
        {
            $path = $filename;
            $filename_arr = explode('.',$filename);
            if(in_array('auth',$filename_arr)) { self::$check_auth = true; }
            array_walk($filename_arr, function($item){
                if(strpos($item,'cache')!==false)
                {
                    self::$use_cache = true;
                    if($timeout = intval(explode('_',$item)[1])) self::$cache_timeout = $timeout;
                }
            });
            break;
        }
        return $path;
    }

    /**
     * Path to config file
     *
     * @return bool|string
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
     */
    protected static function path_verify()
    {
        $module_name = cfg::module();
        $proc_name = cfg::proc();
        $method = cfg::request_method();

        $path = implode('/',array(
            self::path_module(),
            $module_name,
            $proc_name,
            $method.'.'.self::file_verify
        ));

        if(is_file($path)) return $path;
        return false;
    }

    /**
     * Path to verify file
     *
     * @return bool|string
     */
    protected static function path_verify_func()
    {
        $module_name = cfg::module();
        $proc_name = cfg::proc();
        $method = cfg::request_method();

        $path = implode('/',array(
            self::path_module(),
            $module_name,
            $proc_name,
            $method.'.'.self::file_verify_func
        ));

        if(is_file($path)) return $path;
        return false;
    }

    /**
     * 요청바디 설정
     *
     * @param string $key
     * @param string $value
     */
    public static function set_request_param($key, $value) { if($key && ($value || $value===0)) self::$request_param[$key] = $value; }

    /**
     * 요청값 반환
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
     * set failure
     */
    public static function failure() { self::$success = false; }

    /**
     * @return bool
     */
    public static function isSuccess() { return self::$success; }
}
