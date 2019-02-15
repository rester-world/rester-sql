<?php
namespace rester\sql;
use Exception;
use PDO;

/**
 * Class db
 * @author kevinpark@webace.co.kr
 */
class db
{
    /**
     * rester.ini config keyword
     */
    const cfg_name = 'database';

    const cfk_type = 'type';
    const cfk_host = 'host';
    const cfk_port = 'port';
    const cfk_user = 'user';
    const cfk_password = 'password';
    const cfk_database = 'database';

    /**
     * 데이터베이스 정보를 동적으로 받아올 때
     * 호스팅 서비스등
     */
    const type_db_dynamic        = 'dynamic';
    const type_db_dynamic_module = 'module';
    const type_db_dynamic_proc   = 'proc';

    /**
     * @var array 데이터베이스 인스턴스
     */
    private static $inst = array();

    /**
     * @param string $config_name
     *
     * @return bool|PDO
     */
    public static function get($config_name='default')
    {
        try
        {
            if(!is_string($config_name)) throw new Exception("The parameter must be a string.");

            // 처음 호출이면 아래 내용 실행
            if (self::$inst[$config_name] == null)
            {
                $cfg = cfg::Get(self::cfg_name,$config_name);

                // 모듈을 호출하여 데이터베이스 정보를 받아옴
                if($cfg[self::cfk_type]==self::type_db_dynamic)
                {
                    $module = $cfg[self::type_db_dynamic_module];
                    $proc = $cfg[self::type_db_dynamic_proc];
                    if($module && $proc)
                    {
                        $cfg = rester::call_module($module,$proc);
                    }
                    else
                    {
                        throw new Exception("There is no {$config_name} database setting. (module,proc)");
                    }
                }

                if(!$cfg) throw new Exception("There is no {$config_name} database setting.");
                if(!$cfg['type']) throw new Exception("There is no {$config_name}['type'] database setting.");
                if(!$cfg['host']) throw new Exception("There is no {$config_name}['host'] database setting.");
                if(!$cfg['user']) throw new Exception("There is no {$config_name}['user'] database setting.");
                if(!$cfg['password']) throw new Exception("There is no {$config_name}['password'] database setting.");
                if(!$cfg['database']) throw new Exception("There is no {$config_name}['database'] database setting.");

                $dsn = self::create_dsn($cfg);
                self::$inst[$config_name] = new PDO($dsn, $cfg[self::cfk_user], $cfg[self::cfk_password]);
                self::$inst[$config_name]->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$inst[$config_name]->setAttribute(PDO::ATTR_EMULATE_PREPARES, FALSE);
            }
            return self::$inst[$config_name];
        }
        catch (Exception $e)
        {
            rester::failure();
            rester::error($e->getMessage());
            return false;
        }
    }

    /**
     * @param array $db
     *
     * @return string
     * @throws Exception
     */
    private static function create_dsn($db)
    {
        $db_type = strtolower($db[self::cfk_type]);
        $db_host = $db[self::cfk_host];
        $db_port = $db[self::cfk_port];
        $db_database = $db[self::cfk_database];

        if ($db_type == "oracle" || $db_type == "orcl" || $db_type == "oci")
        {
            $dns = "oci:dbname=//{$db_host}:{$db_port}/{$db_database};charset=utf8";
        }
        elseif ($db_type == "mssql" || $db_type == "dblib")
        {
            $dns = "dblib:host={$db_host}:{$db_port};dbname={$db_database};charset=utf8";
        }
        elseif($db_type == 'mysql')
        {
            $dns = $db_type . ":host={$db_host};port={$db_port};dbname={$db_database};charset=utf8";
        }
        else
        {
            throw new Exception("Database type({$db_type}) not supported.");
        }
        return $dns;
    }
}
