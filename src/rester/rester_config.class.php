<?php
namespace rester\sql;
use Exception;
/**
 * Class rester_config
 *
 * @package rester\sql
 */
class rester_config
{
    const file_name = 'config.ini';

    const common = 'common';
    const common_database = 'database';
    const common_database_default = 'default';

    const auth = 'auth';
    const auth_default = 'default';

    const cache = 'cache';
    const cache_default = 'default';

    const access = 'access';
    const access_default = 'default';
    const access_private = 'private';
    const access_internal = 'internal';
    const access_public = 'public';

    /**
     * default value
     * @var array
     */
    protected $data = [
        self::common=>[
            self::common_database=>self::common_database_default
        ]
    ];
    protected $module;

    protected $check_auth = false;
    protected $use_cache = false;
    protected $cache_timeout;

    /**
     * rester_config constructor.
     *
     * @param string $module
     *
     * @throws Exception
     */
    public function __construct($module)
    {
        $this->module = $module;
        $path = $this->path_cfg($module);

        // init
        if($path)
        {
            $cfg = parse_ini_file($path,true, INI_SCANNER_TYPED);
            foreach($cfg as $k=>$v)
            {
                foreach($v as $kk=>$vv)
                {
                    $this->data[$k][$kk] = $vv;
                }
            }
        }
        else
        {
            throw new Exception("There is no config file. Check the config file [{$path}]");
        }
    }

    /**
     * Path to config file
     *
     * @param string $module
     *
     * @return bool|string
     */
    protected function path_cfg($module)
    {
        $path = implode('/',array(
            dirname(__FILE__).'/../'.rester::path_module,
            $module,
            self::file_name
        ));

        if(is_file($path)) return $path;
        return false;
    }

    /**
     * 데이터베이스 설정정보 반환
     *
     * @return bool|array
     */
    public function database()
    {
        return cfg::database($this->data[self::common][self::common_database]);
    }

    /**
     * 권한 검사 유무 체크
     * 1. 기본 false
     * 2. config.ini 에 기본값이 설정된 경우 덮어씀
     * 3. 권한 설정값이 입력되어 있을 경우 해당 값 설정
     *
     * @param string $proc
     *
     * @return bool
     */
    public function is_auth($proc)
    {
        $result = false;
        if($v = $this->data[self::auth][self::auth_default]) $result = $v;
        if($v = $this->data[self::auth][$proc]) $result = $v;
        return $result;
    }

    /**
     * 캐쉬 설정 반환
     * 1. 기본 false
     * 2. config.ini 에 기본값이 설정된 경우 덮어씀
     * 3. 권한 설정값이 입력되어 있을 경우 해당 값 설정
     *
     * @param string $proc
     *
     * @return bool|int
     */
    public function is_cache($proc)
    {
        $result = false;
        if($v = $this->data[self::cache][self::cache_default]) $result = $v;
        if($v = $this->data[self::cache][$proc]) $result = $v;
        return $result;
    }

    /**
     * Access level
     * -------------------------------------------
     * 1. 기본 public
     * 2. config.ini 에 기본값이 설정된 경우 덮어씀
     * 3. 권한 설정값이 입력되어 있을 경우 해당 값 설정
     *
     * @param string $proc
     *
     * @return string
     * @throws Exception
     */
    public function access_level($proc)
    {
        $result = self::access_public;
        if($v = $this->data[self::access][self::access_default]) $result = $v;
        if($v = $this->data[self::access][$proc]) $result = $v;
        if(
            $result==self::access_public ||
            $result==self::access_internal ||
            $result==self::access_private
        ) return $result;

        throw new Exception("Access level must be [public|internal|private]. ({$this->module}/{$proc})");
    }

    /**
     * @param string $section
     * @param string $key
     *
     * @return string|array
     */
    public function get($section='', $key='')
    {
        if($section==='') return $this->data;
        if($section && $key) return $this->data[$section][$key];
        return $this->data[$section];
    }
}
