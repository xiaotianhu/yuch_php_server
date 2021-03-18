<?php 
declare(strict_types=1);
namespace module\event;
/*
 * trigger when there is new message for dispatcher from other process
 */
use module\server\ClientEntity;

class DispatcherMessageQueueEvent extends AbstractEvent{
    public ?ClientEntity $clientEntity = null;
    public int $messageType = 0;
    public string $message = "";

    public function __construct(?ClientEntity $client, int $messageType, string $message)
    {
        $this->clientEntity = $client;
        $this->messageType  = $messageType;
        $this->message      = $message;
    }
}
