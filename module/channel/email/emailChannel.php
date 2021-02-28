<?php 
declare(strict_types=1);
namespace module\channel\email;

use module\channel\BaseChannel;
use module\server\BbMessageEntity;
use module\process\ProcessMessager;
use module\channel\email\pop3Mailer\mailer;
use module\channel\email\pop3Mailer\PopException;
use module\exception\ServerException;

class EmailChannel extends BaseChannel {
    /*
     * type for message queue
     */
    const MESSAGE_TYPE = 1;
    
    private ?ProcessMessager $processMessager = null;
    private ?Mailer $mailer = null;
    public function __construct()
    {
        $this->processMessager = app()->processMessager;
        try{
            $config          = [
                "pop3_host"  =>config("server.pop3_host"),
                "pop3_port"  => intval(config("server.pop3_port")),
                "username"   => config("server.email_username"),
                "password"   => config("server.email_password"),
                "encrypt"    =>config("server.email_encrypt"),
                "max_unread" => config("server.email_max_unread", 20),
                "runtime_path" => BASE_DIR."/runtime/",
            ];
            $this->mailer = new Mailer($config);
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
        try{
            l("emailChannel:checking unread email...");
            $unreadIds = $this->mailer->checkUnread();
            if(empty($unreadIds)) {
                var_dump("no unread emails.");
                return;
            }
            $this->mailer->retrieveUnreads($unreadIds);
        }catch(\Throwable $e){
            var_dump($e->getMessage());
            var_dump($e->getFile());
            var_dump($e->getLine());
            throw $e;
        }
    }
}
