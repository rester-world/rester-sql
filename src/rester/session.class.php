<?php
namespace rester\sql;
use Exception;
use Redis;

/**
 * Class session
 * kevinpark@webace.co.kr
 */
class session
{
    private static $session_id;	// 세션 아이디

    /**
     * 토큰생성
     *
     * @param int $length
     *
     * @return string token
     */
    public static function gen_token($length=40)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ.!@#$%^&()-_*=+';
        $token = '';
        for ($i = 0; $i < $length; $i++) {
            $token .= $characters[rand(0, strlen($characters))];
        }
        return $token;
    }

    /**
     * @param string $token
     *
     * @return bool|string
     * @throws Exception
     */
    public static function get($token)
    {
        $redis_cfg = cfg::cache();
        if(!($redis_cfg['host'] && $redis_cfg['port'])) throw new Exception("Require cache config to use auth.");

        $redis = new Redis();
        if($redis->connect($redis_cfg['host'], $redis_cfg['port'], 1.0))
        {
            if($redis_cfg['auth']) $redis->auth($redis_cfg['auth']);

            if($session_id = $redis->get('token_'.$token))
            {
                self::$session_id =  $session_id;
                $redis->close();
            }
            else
            {
                $redis->close();
                throw new Exception("Can not access interface: require login token.");
            }
        }
        else
        {
            throw new Exception("Can not access redis server.");
        }
        return $session_id;
    }

    /**
     * @param string $id
     *
     * @return string
     */
    public static function set($id)
    {
        try
        {
            if(!$id) { throw new Exception("Require first parameter(id:string)"); }

            $timeout = intval(cfg::Get('session','timeout'));
            $redis_cfg = cfg::cache();
            if(!($redis_cfg['host'] && $redis_cfg['port'])) throw new Exception("Require cache config to use auth.");

            $redis = new Redis();
            $redis->connect($redis_cfg['host'], $redis_cfg['port']);
            if($redis_cfg['auth']) $redis->auth($redis_cfg['auth']);

            do {
                $token = self::gen_token();
            } while($redis->get('token_'.$token));

            $redis->set('token_'.$token,$id,$timeout);
            $redis->close();

            self::$session_id = $id;
            return $token;
        }
        catch (Exception $e)
        {
            rester_response::error($e->getMessage());
        }
        return false;
    }

    /**
     * @return string
     */
    public static function id()
    {
        return self::$session_id;
    }
}
