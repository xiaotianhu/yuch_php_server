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
    $r = $config;
    foreach(explode(".", $key) as $v){
        if(empty($r[$v])) {
            return $default;
        }
        $r = $r[$v];
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

function debug($log)
{
    if(getenv("DEBUG")) l($log);
}

function loadDir(string $dir, bool $recursive=false)
{
    if(!is_dir($dir)) throw new \Exception($dir."not exist.");
    if(substr($dir, -1, 1) != "/") $dir = $dir."/";

    $files = scandir($dir);
    foreach($files as $file){
        if($file == "." || $file == "..") continue;
        if(is_dir($dir.$file) && $recursive) {
            loadDir($dir.$file, true);
        }else{
            include_once($dir.$file);
        }
    }
}

function curl($url, $params=[], $header=[]) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    if (!empty($params)) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    }
    if (!empty($header)) curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $result = curl_exec($ch);
    if ($result === false) $result = curl_errno($ch);

    curl_close($ch);
    return $result;
}

