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
        l(intval($this->socket)."last heartbeat time:".$this->lastHeartbeat);
    }

    /*
     * Check if last heartbeat is timeouted.Disconnect if timeout.
     */
    public function isTimeout():bool
    {
        if(!$this->lastHeartbeat) return true;
        if(time() - $this->lastHeartbeat > self::TIMEOUT_SEC) 
            throw new ClientException("Client connection timeouted.Disconnecting...");

        return true;
    }

    /*
     * send message to blackberry client.
     */
    public function sendToBb(BbMessageEntity $messageEntity):bool
    {
        $outPackage = "";
        $this->writeByte($outPackage, 0);//msg head
        $this->writeByte($outPackage, 4);
        $this->writeInt($outPackage, 1);
        $this->writeStringArray($outPackage, $messageEntity->mailFrom);//from
        $this->writeStringArray($outPackage, $messageEntity->mailReplyTo??[]);//reply to
        $this->writeStringArray($outPackage, $messageEntity->mailCcTo??[]);//cc
        $this->writeStringArray($outPackage, $messageEntity->mailBccTo??[]);//bcc
        $this->writeStringArray($outPackage, $messageEntity->mailTo);//to
        $this->writeStringArray($outPackage, $messageEntity->group??[]);//group

        $this->writeString($outPackage, $messageEntity->subject);
        $this->writeLong($outPackage, ceil(microtime(true)*1000));

        $this->writeInt($outPackage, 0);

        $this->writeString($outPackage, $messageEntity->xMailName);
        $this->writeString($outPackage, $messageEntity->contain);
        $this->writeString($outPackage, $messageEntity->containHtml);

        $this->writeInt($outPackage, 0);//attachments num
        $this->writeBool($outPackage, false);//has location

        $this->writeString($outPackage, 'text/plain');//contain html type
        $this->writeString($outPackage, config("server.email_username"));//own account
        $this->writeString($outPackage, $messageEntity->id??'id');//message id
        $this->writeString($outPackage, '');//in reply to
        $this->writeString($outPackage, '');//reference id

        $this->addPackageToBuffer($outPackage);
        $this->sendToClient();
        debug("send emailentity to bb...".json_encode($messageEntity));
        return true;
    }
}
