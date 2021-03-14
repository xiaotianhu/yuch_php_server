<?php 
declare(strict_types=1);
namespace module\listener;

use module\server\BbMessageEntity;
use module\channel\ChannelManager;

class NewBbMessageListener extends AbstractListener{
    private ?ChannelManager $channelManager = null;

    public function handle($event):void
    {
        $package     = $event->package;
        $client      = $event->client;
        $emailEntity = new BbMessageEntity($client, $package);
        $emailEntity->parseFromPackage();

        debug("received email from bb....");
        $this->channelManager = app()->channelManager;
        $this->channelManager->sendByChannel($emailEntity);
    }
}


