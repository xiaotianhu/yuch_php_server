<?php 
declare(strict_types=1);
namespace module\listener;

use module\server\EmailEntity;

class NewMailListener extends AbstractListener{
    
    public function handle($event):void
    {
        $package     = $event->package;
        $client      = $event->client;
        $emailEntity = new EmailEntity($client, $package);
        $emailEntity->parseFromPackage();

        l("received email....");
        var_dump($emailEntity);
    }
}


