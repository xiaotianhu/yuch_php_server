<?php 
/*
 * entry of server
 * @date  2021.01.01
 * @author xiaotianhu
 */
use module\server\Server;
use module\exception\ServerException;

define("BASE_DIR", realpath(dirname(__FILE__)));
(function(){
    include(BASE_DIR."/vendor/autoload.php");
    if(!is_file(BASE_DIR."/vendor/autoload.php")){
        exit ("you should run: 'composer install' before start the server.");
    }
})();

try{
    $app = (new Server());
    $app->start();
}catch(ServerException $e){
    l($e->getMessage());
    var_dump($e->getMessage());
    exit(); 
}catch(\Throwable $e){
    var_dump($e->getMessage());
    exit(); 
}
