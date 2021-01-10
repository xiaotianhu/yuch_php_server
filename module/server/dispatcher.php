<?php 
declare(strict_types=1);
namespace module\server;

use module\event\NewClientEvent;
class Dispatcher {
    
    public function handle(ClientEntity $client)
    {

        //event(new NewClientEvent($client));
    }
}
