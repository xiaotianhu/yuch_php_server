<?php 
/*
 * entry of server
 * @date  2021.01.01
 * @author xiaotianhu
 */
use module\server\Server;

(function(){
    $dir = dirname(__FILE__);
    include($dir."/module/server/loader.php");
    loadDir($dir."/module", true);
    loadDir($dir."/config", true);

    if(!is_file($dir."/vendor/autoload.php")){
        l("you should run: 'composer install' before start the server.");
        exit();
    }
    include($dir."/vendor/autoload.php");
})();

$app = (new Server());
$app->start();
