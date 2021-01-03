<?php 
declare(strict_types=1);

/*
 * get config.Usage: config("server.port") 
 * return $default if $key is not exist
 * @param string $key 
 * @param mixed $default 
 */
function config(string $key, $default = null) 
{
    if(empty($key)) return null;
    global $_CONFIG;
    $r = null;
    foreach(explode(".", $key) as $v){
        if(empty($_CONFIG[$v])) return $default;
        $r = $_CONFIG[$v];
    }
    return empty($r)? $r : $default;
}

function l($log)
{
    if(!$log || empty($log)) return false;
    $logDir = BASE_DIR."/log/";
    $logFile = date("Y-m-d", time()).".log"; 
    if(!is_string($log)){
        $log = json_encode($log);
    }
    $log = "[".date("Y-m-d H:i:s", time())."] ".$log."\r\n";
    echo $log;
    file_put_contents($logDir.$logFile, $log, FILE_APPEND);
}
