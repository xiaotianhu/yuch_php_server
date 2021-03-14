<?php 
declare(strict_types=1);
namespace module\event;
/*
 * message has been send to blackberry
 */
use module\server\ClientEntity;
use module\server\BbMessageEntity;
class SendToBbEvent extends AbstractEvent{
    public ?ClientEntity $clientEntity     = null;
    public ?BbMessageEntity $messageEntity = null;

    public function __construct(?ClientEntity $client, ?BbMessageEntity $messageEntity)
    {
        $this->clientEntity  = $client;
        $this->messageEntity = $messageEntity;
    }
}
