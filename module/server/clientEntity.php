<?php 
declare(strict_types=1);
namespace module\server;

class ClientEntity {
    use SendReceiveTrait;
    
    /*
     * @var Resource of socket connection
     */
    private $socket = null;
    
    /*
     * @var last heartbeat timestamp
     */
    private ?int $lastHeartbeat = null;

    public function __construct($socket)
    {
        if(empty($socket) || !$socket){
            throw new \Exception("Socket resource is not available.");
        }
        $this->socket = $socket;
        l("A new client has connected:".$this->getIp);
        return $this;
    }

    public function validate()
    {
        $realPassword = config("server.password");
        $this->receivePackage();
        $head = $this->slice($this->package,1);
        l("real password:".$realPassword);
        var_dump($head);die(); 
    }

    public function getIp():?string
    {
        $ip = null;
        socket_getpeername($this->socket, $ip);
        return $ip;
    }
}
