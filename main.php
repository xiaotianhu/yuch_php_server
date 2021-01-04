<?php 
/*
 * entry of server
 * @date  2021.01.01
 * @author xiaotianhu
 */
use module\server\Server;
use module\exception\ServerException;

(function(){
    $dir = dirname(__FILE__);
    include($dir."/vendor/autoload.php");
    include($dir."/module/server/loader.php");
    loadDir($dir."/module", true);
    if(!is_file($dir."/vendor/autoload.php")){
        l("you should run: 'composer install' before start the server.");
        exit();
    }
})();

try{
    $app = (new Server());
    $app->start();
}catch(ServerException $e){
    l($e);
    exit(); 
}
