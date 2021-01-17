<?php 
declare(strict_types=1);
namespace module\server;

use module\event\NewClientEvent;
class Dispatcher {
    
    public function handle(ClientEntity $client)
    {

        //event(new NewClientEvent($client));
        while(true){
            sleep(1);
            echo "ok\r\n";
        }
    }
}
