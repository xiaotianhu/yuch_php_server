<?php 
declare(strict_types=1);
namespace module\listener;

class HeartbeatListener extends AbstractListener{
    
    public function handle($event):void
    {
        l("received heartbeat.");
        $client = $event->client;
        $client->updateHeartbeat();
    }
}


