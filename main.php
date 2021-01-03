<?php 
/*
 * entry of server
 * @date  2021.01.01
 * @author xiaotianhu
 */
include(dirname(__FILE__)."/module/server/Loader.php");
$loader = new module\server\Loader();
$loader->loadDir(dirname(__FILE__)."/module", true);
$loader->loadDir(dirname(__FILE__)."/config", true);
