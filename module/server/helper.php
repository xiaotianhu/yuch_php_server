<?php 
declare(strict_types=1);

/*
 * get the global $app object
 */
function app()
{
    global $app;
    return $app;
}

/*
 * get config.Usage: config("server.port") 
 * return $default if $key is not exist
 * @param string $key 
 * @param mixed $default 
 */
function config(string $key, $default = null) 
{
    if(empty($key)) return null;
    $config = app()->configs;
    $r = null;
    foreach(explode(".", $key) as $v){
        if(empty($config[$v])) {
            return $default;
        }
        $r = $config[$v];
    }
    return !empty($r)? $r : $default;
}

/*
 * dispatch a event by event object.
 */
function event(object $event)
{
    l("new event: ".get_class($event));
    app()->eventDispatcher->dispatchEvent(get_class($event), $event);  
}

function l($log)
{
    if(!$log || empty($log)) return false;
    $logDir  = BASE_DIR."/runtime/log/";
    $logFile = date("Y-m-d", time()).".log";
    if(!is_dir($logDir)) mkdir($logDir);

    if($log instanceof Exception){
        $e = $log;
        $log = sprintf("File: %s(%d) ,ERROR: %s",$e->getFile(), $e->getLine(), $e->getMessage());
    } else if(!is_string($log)){
        $log = json_encode($log);
    }

    $log = "[".date("Y-m-d H:i:s", time())."] ".$log."\r\n";
    echo $log;
    file_put_contents($logDir.$logFile, $log, FILE_APPEND);
}
