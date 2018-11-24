<?php if(!defined('__RESTER__')) exit;

/**
 * @return string 클라이언트의 접속 아이피
 */
function GetRealIPAddr()
{
    //check ip from share internet
    if (!empty($_SERVER['HTTP_CLIENT_IP']))
    {
        $ip=$_SERVER['HTTP_CLIENT_IP'];
    }
    //to check ip is pass from proxy
    else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
    {
        $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    else
    {
        $ip=$_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

/**
 * module/config.ini 설정을 반환함
 *
 * @param null|string $section
 * @param null|string $key
 *
 * @return array|bool|mixed
 */
function cfg($section=null,$key=null)
{
    $cfg = array();
    if($path = rester::path_cfg())
    {
        $cfg = parse_ini_file($path,true, INI_SCANNER_TYPED);
    }
    if($section===null) return $cfg;
    if($key===null) return $cfg[$section];

    $result = $cfg[$section][$key];
    if(!$result) $result = cfg::Get($section,$key);

    return $result;
}
