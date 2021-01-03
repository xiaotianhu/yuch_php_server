<?php 
declare(strict_types=1);
namespace module\server;

class ClientEntity {
    private $socket = null;

    public function __construct($socket)
    {
        if(empty($socket) || !$socket){
            throw new \Exception("Socket resource is not available.");
        }
        $this->socket = $socket;
        l("A new client has connected:".$this->getIp);
        return $this;
    }

    public function getIp():?string
    {
        $ip = null;
        socket_getpeername($this->socket, $ip);
        return $ip;
    }
}
