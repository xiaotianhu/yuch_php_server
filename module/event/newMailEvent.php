<?php 
declare(strict_types=1);
namespace module\event;
/*
 * trigger when new client has connected.
 */
use module\server\ClientEntity;

class NewClientEvent extends AbstractEvent{
    public ?ClientEntity $clientEntity = null;
    
    public function __construct(?ClientEntity $client)
    {
        $this->clientEntity = $client;
    }
}
