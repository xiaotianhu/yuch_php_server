<?php 
declare(strict_types=1);
namespace module\server;

use module\exception\ClientException;
class ClientEntity {
    use DataTrait;
    const HEAD_CONFIRM   = 2;
    const HEAD_HEARTBEAT = 7;
    const HEAD_MAIL      = 0;

    const TIMEOUT_SEC = 300;//5minutes timeout if no heartbeat reveived.
    
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
        l("A new client has connected:".$this->getIp());
        $this->validate();
        return $this;
    }

    public function validate()
    {
        $realPassword = config("server.password");
        $package = $this->receivePackage();
        
        $head = $package->readByte();
        if($head != self::HEAD_CONFIRM) throw new ClientException("validation head wrong.");
        $pwd  = $package->readString();
        if($pwd != $realPassword) throw new ClientException("password worng.");
        l("real password:".$realPassword);
        $version = $package->readInt();
        l("client version: $version ");
        l($package->readString());
    }

    public function getIp():?string
    {
        $ip = null;
        socket_getpeername($this->socket, $ip);
        return $ip;
    }

    public function updateHeartbeat()
    {
        $this->lastHeartbeat = time();
    }

    /*
     * Check if last heartbeat is timeouted.Disconnect if timeout.
     */
    public function isTimeout():bool
    {
        if(!$this->lastHeartbeat) return true;
        if(time() - $this->lastHeartbeat > self::TIMEOUT_SEC) 
            throw new ClientException("Client connection timeouted.Disconnecting...");
    }
}
