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

    /*
     * refresh interval for new mail though pop3
     */
    const REFRESH_INTERVAL = 120;//seconds

    const RUNTIME_PATH = BASE_DIR."/runtime";

    private ?ProcessMessager $processMessager = null;
    private ?Mailer $mailer = null;
    public function __construct()
    {
        $this->processMessager = app()->processMessager;
        try{
            $config            = [
                "pop3_host"    => config("server.pop3_host"),
                "pop3_port"    => intval(config("server.pop3_port")),
                "username"     => config("server.email_username"),
                "password"     => config("server.email_password"),
                "encrypt"      => config("server.email_encrypt"),
                "max_unread"   => config("server.email_max_unread", 20),
                "runtime_path" => self::RUNTIME_PATH,
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
            sleep(self::REFRESH_INTERVAL);
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
                debug("no more unread mails.");
                return;
            }
            debug("unread email id:".json_encode($unreadIds));
            $this->saveUnreadEmail($this->mailer->retrieveUnreads($unreadIds));
        }catch(\Throwable $e){
            debug($e->getMessage()."file:".$e->getFile()."line:".$e->getLine());
            throw $e;
        }
    }

    private function saveUnreadEmail(?array $parsedMails):bool
    {
        $saveUnreadPath = self::RUNTIME_PATH.Mailer::MAILER_PATH."/unreads/";
        if(!is_dir($saveUnreadPath)) mkdir($saveUnreadPath, 0777, true);
        if(empty($parsedMails)) return false;

        foreach($parsedMails as $mailArr){
            $tmpArr = $mailArr;
            $id = $mailArr['id'];
            if(!empty($mailArr['attachments'])) {
                unset($tmpArr['attachments']);
                if(!is_dir($saveUnreadPath.$id)) mkdir($saveUnreadPath.$id, 0777);
                foreach($mailArr['attachments'] as $f => $v){
                    rename($v, $saveUnreadPath.$id."/".$f);
                    debug("saved email's attachment :".$saveUnreadPath.$id."/".$f);
                }
            }
            $str = json_encode($tmpArr);
            file_put_contents($saveUnreadPath.$id."_unread.json", $str);
        }
        return true;
    }
}
