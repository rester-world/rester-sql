<?php
namespace rester\sql;

/**
 * Class rester_response
 *
 * @package rester\sql
 */
class rester_response
{
    protected static $response_code = 200;

    protected static $success = true;
    protected static $msg = [];
    protected static $warning = [];
    protected static $error = [];
    protected static $error_trace = [];
    protected static $data = [];

    public static function run()
    {
        http_response_code(self::$response_code);
        header("Content-type: application/json; charset=UTF-8");

        $response_body = array(
            'success'=>self::$success,
            'msg'=>self::$msg,
            'error'=>self::$error,
            'error_trace'=>self::$error_trace,
            'warning'=>self::$warning,
            'data'=>self::$data
        );

        echo json_encode($response_body);
    }

    /**
     * reset data
     */
    public static function reset()
    {
        self::$success = true;
        self::$msg = [];
        self::$warning = [];
        self::$error = [];
        self::$data = [];
    }

    /**
     * @param array$data
     */
    public static function body($data)
    {
        self::$data = $data;
    }

    /**
     * Add message
     *
     * @param string $msg
     */
    public static function msg($msg) { self::$msg[] = $msg; }

    /**
     * Add warning message
     *
     * @param string $msg
     */
    public static function warning($msg) { self::$warning[] = $msg; }

    /**
     * Add error
     *
     * @param string $msg
     */
    public static function error($msg) { self::$error[] = $msg; self::failure(); }

    /**
     * Set error trace
     * @param array $data
     */
    public static function error_trace($data) { self::$error_trace = $data; self::failure(); }

    /**
     * set failure
     */
    public static function failure() { self::$success = false; }
}
