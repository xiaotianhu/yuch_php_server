<?php 
declare(strict_types=1);
namespace module\server;

use module\event\NewClientEvent;
use module\event\HeartbeatEvent;

class Dispatcher {
    const RECV_SLEEP_TIME =1;//non block sleep interval
    
    public function handle(ClientEntity $client)
    {
        event(new NewClientEvent($client));
        while(true){
            l("准备收package");
            $package = $client->receivePackage(); 
            $this->onPackage($client, $package);
            $client->isTimeout();
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
        }
    }
}
