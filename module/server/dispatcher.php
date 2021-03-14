<?php 
declare(strict_types=1);
namespace module\server;

use module\event\NewClientEvent;
use module\event\HeartbeatEvent;
use module\event\NewBbMessageEvent;
use module\event\DispatcherMessageQueueEvent;
use module\process\ProcessMessager;

class Dispatcher {
    const RECV_SLEEP_TIME =1;//non block sleep interval
    
    public function handle(ClientEntity $client)
    {
        event(new NewClientEvent($client));
        $this->checkMessageQueue($client);
        while(true){
            debug("wait for new package...");
            $package = $client->receivePackage(); 
            $this->onPackage($client, $package);
            $client->isTimeout();
            //$this->checkMessageQueue($client);
            sleep(self::RECV_SLEEP_TIME);
        }
    }

    public function onPackage(ClientEntity $client, PackageEntity $package)
    {
        $packageHead = $package->readByte(); 
        switch($packageHead){
            case ClientEntity::HEAD_HEARTBEAT:
                event(new HeartbeatEvent($client, $package));
                break; 
            case ClientEntity::HEAD_MAIL:
                event(new NewBbMessageEvent($client, $package));
                break;
            default:
                l("undefined package head.".$packageHead);
                var_dump($packageHead);
                var_dump($package);
                break;
        }
    }

    private function checkMessageQueue(ClientEntity $client)
    {
        debug("dispatcher checking message queue...");
        $messager = app()->processMessager;
        $types = ProcessMessager::getAllDispatcherTypes();
        foreach($types as $type){
            $msg = $messager->receive($type); 
            if(!empty($msg)){
                debug("new message for dispatcher: type-{$type} msg-{$msg}");
                event(new DispatcherMessageQueueEvent($client, $type, $msg));
            }
        }
    }
}
