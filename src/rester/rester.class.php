<?php
namespace rester\sql;
use Exception;
use PDO;
use Redis;

/**
 * Class rester
 *
 * @package rester\sql
 */
class rester
{
    const path_module = 'modules';

    /**
     * @var rester_config
     */
    protected $cfg;

    /**
     * @var rester_verify
     */
    protected $verify;

    /**
     * @var string
     */
    protected $module;

    /**
     * @var string
     */
    protected $proc;

    /**
     * @var string
     */
    protected $path_proc;

    /**
     * @var string
     */
    protected $path_proc_sql;

    /**
     * @var Redis
     */
    protected $redis;

    /**
     * @var bool
     */
    protected $check_auth;

    /**
     * @var bool | int
     */
    protected $cache_timeout;

    /**
     * @var string
     */
    protected $cache_key;

    /**
     * @var bool 외부접근 여부
     */
    protected $is_public_access;

    /**
     * rester constructor.
     *
     * @param string $module
     * @param string $proc
     * @param array  $request_data
     *
     * @throws Exception
     */
    public function __construct($module, $proc, $request_data=[])
    {
        $this->is_public_access = false;
        $this->module = $module;
        $this->proc = $proc;

        $base_path = dirname(__FILE__).'/../'.self::path_module;

        // sql 프로시저 경로 설정
        $this->path_proc_sql = false;
        $path = implode('/',array( $base_path, $module, $proc.'.sql' ));
        if(is_file($path))
        {
            $this->path_proc_sql = $path;
        }

        // 프로시저 경로 설정
        $this->path_proc = false;
        $path = implode('/',array( $base_path, $module, $proc.'.php' ));
        if(is_file($path))
        {
            $this->path_proc = $path;
        }

        // 프로시저 파일 체크
        if(!$this->path_proc_sql && !$this->path_proc)
        {
            throw new Exception("Not found procedure. Module: {$module}, Procedure: {$proc} ");
        }

        // create config
        $this->cfg = new rester_config($module);

        // create verify
        $this->verify = new rester_verify($module, $proc);
        $this->verify->validate($request_data);

        // check auth
        $this->check_auth = $this->cfg->is_auth($proc);

        // check cache
        $this->cache_timeout = $this->cfg->is_cache($proc);

        // set redis
        $this->redis = false;
        if($this->cache_timeout)
        {
            $redis_cfg = cfg::cache();
            if(!($redis_cfg['host'] && $redis_cfg['port']))
                throw new Exception("Require cache config to use cache.");

            $this->redis = new Redis();
            $this->redis->connect($redis_cfg['host'], $redis_cfg['port']);
            if($redis_cfg['auth']) $this->redis->auth($redis_cfg['auth']);

            $this->cache_key = implode('_', array_merge(array($module,$proc),$this->verify->param()));
        }
    }

    public function __destruct()
    {
        if($this->redis) $this->redis->close();
    }

    /**
     * 외부접근 상태로 설정
     */
    public function set_public_access()
    {
        $this->is_public_access = true;
    }

    /**
     * run rester
     *
     * @throws Exception
     */
    public function run()
    {
        // check access level [public]
        if($this->is_public_access)
        {
            $access_level = $this->cfg->access_level($this->proc);
            if($access_level != rester_config::access_public)
                throw new Exception("Can not access procedure. [Module] {$this->module}, [Procedure] {$this->proc}, [Access level] {$access_level} ");
        }

        // check auth
        if($this->check_auth) { session::get(cfg::token()); }

        $response_data = false;

        // get cached data
        if($this->cache_timeout)
        {
            $response_data = json_decode($this->redis->get($this->cache_key),true);
        }

        // include procedure
        if(!$response_data)
        {
            if($this->path_proc_sql)
            {
                $response_data = $this->execute_sql($this->path_proc_sql);
            }
            elseif($this->path_proc)
            {
                $response_data = include $this->path_proc;
            }

            // cached body
            if($this->cache_timeout)
            {
                $this->redis->set($this->cache_key,json_encode($response_data),$this->cache_timeout);
            }
        }
        return $response_data;
    }

    /**
     * @param string $path
     *
     * @return array
     * @throws Exception
     */
    public function execute_sql($path)
    {
        $pdo = db::get($this->cfg->database());
        $query = file_get_contents($path);

        // 필터링 된 파라미터를 받아옴
        // 영문숫자_-로 조합된 키워드 추출
        $params = [];
        preg_match_all('/:[a-zA-z0-9_-]+/', $query, $matches);
        $matches = $matches[0];

        foreach($matches as $bind_param)
        {
            foreach ($this->verify->param() as $k=>$v)
            {
                if(strpos($k,':')!==0) $k = ':'.$k;
                if($bind_param==$k) $params[$bind_param] = $v;
            }
            if(!isset($params[$bind_param]))
                throw new Exception("There is no parameter for bind. [{$bind_param}]");
        }

        $response_data = [];
        $stmt = $pdo->prepare($query,[PDO::ATTR_CURSOR, PDO::CURSOR_FWDONLY]);
        $stmt->execute($params);
        $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach($res as $row)
        {
            $response_data[] = $row;
        }

        return $response_data;
    }

    /**
     * @param string $key
     *
     * @return bool|mixed
     */
    public function request_param($key)
    {
        return $this->verify->param($key);
    }

    /**
     * @return string
     */
    public function module() { return $this->module; }

    /**
     * @return string
     */
    public function proc() { return $this->proc; }
}
