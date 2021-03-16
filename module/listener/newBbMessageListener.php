<?php 
declare(strict_types=1);
namespace module\listener;

use module\server\BbMessageEntity;
use module\channel\ChannelManager;
use module\server\ClientEntity;

class NewBbMessageListener extends AbstractListener{
    private ?ChannelManager $channelManager = null;
    private ?ClientEntity $clientEntity     = null;
    private ?BbMessageEntity $messageEntity = null;

    public function handle($event):void
    {
        $package            = $event->package;
        $this->clientEntity = $event->client;
        $messageEntity      = new BbMessageEntity($package);
        $messageEntity->parseFromPackage();
        $this->messageEntity = $messageEntity;

        debug("received email from bb....");
        $this->channelManager = app()->channelManager;
        $this->channelManager->sendByChannel($messageEntity);

        $this->sendConfirm();
    }


    /*
     * notify BB send hasbeen done.
     */
    public function sendConfirm()
    {
        $newPackage = "";
        $client = $this->clientEntity;
        $client->writeByte($newPackage, ClientEntity::HEAD_MAIL_SENT);
        $client->writeByte($newPackage, 1);
        $client->writeLong($newPackage, $this->messageEntity->time);
        $client->addPackageToBuffer($newPackage);
        $client->sendToClient();
        $client->addPackageToBuffer($newPackage);
        $client->sendToClient();
    }
}


