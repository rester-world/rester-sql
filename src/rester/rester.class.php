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

    const cfg_file_name = 'config.ini';

    const cfg_common = 'common';
    const cfg_common_database = 'database';
    const cfg_common_database_default = 'default';

    const cfg_auth = 'auth';
    const cfg_cache = 'cache';

    protected static $request_param = [];
    protected static $response_body = null;
    protected static $response_code = 200;

    protected static $success = true;
    protected static $msg = [];
    protected static $warning = [];
    protected static $error = [];

    protected static $cfg = [];
    protected static $cfg_default = [
        self::cfg_common=>[
            self::cfg_common_database=>self::cfg_common_database_default
        ]
    ];
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
     * verify request parameter
     * check body | query string
     *
     * @throws Exception
     */
    protected static function check_parameter()
    {
        if($path_verify = self::path_verify())
        {
            self::reset_parameter();
            $schema = new schema($path_verify);
            if($data = $schema->validate(cfg::parameter()))
                foreach($data as $k => $v) rester::set_request_param($k, $v);
        }
    }

    /**
     * @throws Exception
     */
    protected static function init_config()
    {
        $cfg = array();
        if($path = self::path_cfg())
        {
            $cfg = parse_ini_file($path,true, INI_SCANNER_TYPED);
        }

        // set default value
        foreach(self::$cfg_default as $k=>$values)
        {
            foreach($values as $kk=>$value)
            {
                if(!isset($cfg[$k][$kk]))
                {
                    $cfg[$k][$kk] = $value;
                }
            }
        }
        self::$cfg = $cfg;

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
        /// Because to use another function.
        //=====================================================================
        self::init_config();

        //=====================================================================
        /// check cache option and auth option
        //=====================================================================
        if(self::cfg(self::cfg_auth,$proc)) self::$check_auth = true;
        if(self::cfg(self::cfg_cache,$proc))
        {
            self::$use_cache = true;
            self::$cache_timeout = self::cfg(self::cfg_cache,$proc);
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
            if($data = $schema->validate(cfg::parameter()))
                foreach($data as $k => $v) rester::set_request_param($k, $v);
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
                $response_data = self::execute_sql($path_sql);
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
     * @param string $path
     *
     * @return array
     * @throws Exception
     */
    public static function execute_sql($path)
    {
        // 필터링 된 파라미터를 받아옴
        $params = [];
        foreach (cfg::parameter() as $k=>$v) $params[$k] = self::param($k);

        $pdo = self::db_instance();
        $query = file_get_contents($path);
        $stmt = $pdo->prepare($query,[PDO::ATTR_CURSOR, PDO::CURSOR_FWDONLY]);
        $stmt->execute($params);
        $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $response_data = [];
        foreach($res as $row)
        {
            $response_data[] = $row;
        }
        return $response_data;
    }

    /**
     * @return bool|PDO
     */
    public static function db_instance()
    {
        $dbname = self::cfg(self::cfg_common,self::cfg_common_database);
        if(!$dbname) $dbname = self::cfg_common_database_default;
        return db::get($dbname);
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
        $old_cfg = self::$cfg;

        $res = false;
        try
        {
            $_POST = $query;
            unset($query);
            cfg::init_parameter();
            self::check_parameter();
            self::init_config();

            $path_sql = self::path_sql();
            $path_proc = self::path_proc();

            if($path_sql)
            {
                $res= self::execute_sql($path_sql);
            }
            elseif($path_proc)
            {
                $res= include $path_proc;
            }
            else
            {
                self::failure();
                self::error("Can not found module: {$module}");
            }
        }
        catch (Exception $e)
        {
            self::failure();
            self::error($e->getMessage());
        }


        self::$cfg = $old_cfg;
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
        $old_cfg = self::$cfg;

        $res = false;

        try
        {
            $_POST = $query;
            unset($query);
            cfg::init_parameter();
            self::check_parameter();
            self::init_config();

            $path_sql = self::path_sql();
            $path_proc = self::path_proc();

            if($path_sql)
            {
                $res= self::execute_sql($path_sql);
            }
            elseif($path_proc)
            {
                $res= include $path_proc;
            }
            else
            {
                self::failure();
                self::error("Can not found procedure: {$proc}");
            }
        }
        catch (Exception $e)
        {
            self::failure();
            self::error($e->getMessage());
        }

        self::$cfg = $old_cfg;
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
            self::cfg_file_name
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
    public static function reset_parameter() { self::$request_param = []; }

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
     * Add error
     *
     * @param null|string $msg
     *
     * @return array
     */
    public static function error($msg=null)
    {
        if($msg===null) return self::$error;
        else self::$error[] = $msg;
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
