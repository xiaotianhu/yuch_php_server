<?php 
declare(strict_types=1);
namespace module\channel\email;

use module\channel\BaseChannel;
use module\server\BbMessageEntity;
use module\process\ProcessMessager;
use module\channel\email\pop3Mailer\PopMailer;
use module\channel\email\pop3Mailer\PopException;
use module\exception\ServerException;

class EmailChannel extends BaseChannel {
    /*
     * type for message queue
     */
    const MESSAGE_TYPE = 1;
    
    private ?ProcessMessager $processMessager = null;
    private ?PopMailer $popMailer = null;
    public function __construct()
    {
        $this->processMessager = app()->processMessager;
        try{
            $this->popMailer = new PopMailer(
                config("server.pop3_host"),
                intval(config("server.pop3_port")),
                config("server.email_username"),
                config("server.email_password"),
                config("server.email_encrypt")
            );
            $this->popMailer->reconnect();
        }catch(\Throwable $e){
            l("pop exception:".$e->getMessage());
            throw new ServerException($e->getMessage());
        }
    }

    public function loop()
    {
        while(true){
            $this->checkMessageQueue();
            $this->checkUnreadEmail();
            sleep(5);
        }
    }

    public function checkMessageQueue():void
    {
        l("email channel: checking message queue...");
        $msg = $this->processMessager->receive(self::MESSAGE_TYPE);
        //if($msg) var_dump($msg);
    }

    public function sendByChannel(BbMessageEntity $bbMessageEntity):bool
    {
        return true;
    }

    private function checkUnreadEmail()
    {
        l("emailChannel:checking unread email...");
        $arr = $this->popMailer->listMail();
        var_dump($arr);
    }
}
