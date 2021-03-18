<?php 
declare(strict_types=1);
namespace module\channel\email;

use module\channel\BaseChannel;
use module\server\BbMessageEntity;
use module\process\ProcessMessager;
use module\channel\email\pop3Mailer\Mailer;
use module\channel\email\pop3Mailer\PopException;
use module\exception\ServerException;

class EmailChannel extends BaseChannel {

    /*
     * refresh interval for new mail though pop3
     */
    const REFRESH_INTERVAL = 120;//seconds

    const RUNTIME_PATH = BASE_DIR."/runtime";
    const UNREAD_PATH = self::RUNTIME_PATH.Mailer::MAILER_PATH."/unreads/";

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
            $this->notifyUnreadToDispatcher();
            sleep(self::REFRESH_INTERVAL);
        }
    }

    public function checkMessageQueue():void
    {
        l("email channel: checking message queue...");
        $msg = $this->processMessager->receive(ProcessMessager::EMAIL_CHANNEL_SEND_MSG);
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

    public static function loadUnreadFileToMessageEntity(string $filename):?BbMessageEntity
    {
        $saveUnreadPath = self::UNREAD_PATH;
        if(!is_file($saveUnreadPath.$filename)) return null;
        $content = json_decode(file_get_contents($saveUnreadPath.$filename), true);
        if(!$content) return null;
        $entity = new BbMessageEntity(null);
        $data               = [
            'mailIndex'     => $content['id'],
            'mailFrom'      => [$content['from']],
            'mailReplyTo'   => empty($content['reply_to'])? [] : [$content['reply_to']],
            'mailCcTo'      => empty($content['cc'])? [] : [$content['cc']],
            'mailBccTo'     => empty($content['bcc'])? [] : [$content['bcc']],
            'mailTo'        => [$content['to']],
            'group'         => [],
            'subject'       => $content['subject']??'无标题',
            'time'          => empty($content['date'])? 0 : strtotime($content['date']),
            'contain'       => $content['text_content']??'',
            'containHtml'   => $content['html_content']??'',
            'attachmentNum' => 0,
            'attachments'   => [],
            'filename'      => $content['filename']??'',
        ];
        $entity->unserialize(json_encode($data));
        return $entity;
    }

    public static function removeSavedEmail(string $filename):bool
    {
        $saveUnreadPath = self::UNREAD_PATH;
        if(!is_file($saveUnreadPath.$filename)) return false;
        $ex = explode("_", $filename);
        if(empty($ex[0])) return false;
        if(!unlink($saveUnreadPath.$filename)) return false;
        $id = $ex[0];
        if(is_dir($saveUnreadPath."/".$id)){
            if(!unlink($saveUnreadPath."/".$id)) return false;
        }
        return true;
    }

    private function saveUnreadEmail(?array $parsedMails):bool
    {
        $saveUnreadPath = self::UNREAD_PATH;
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
            $filename = $id."_unread.json";
            $tmpArr['filename'] = $filename;
            $str = json_encode($tmpArr);
            file_put_contents($saveUnreadPath.$filename, $str);
        }
        return true;
    }

    private function notifyUnreadToDispatcher()
    {
        $saveUnreadPath = self::UNREAD_PATH;
        if(!is_dir($saveUnreadPath)) return;
        $files = scandir($saveUnreadPath);
        $newMails = [];
        foreach($files as $file){
            if(substr($file, -4, 4) == 'json') $newMails[] = $file; 
        }
        if(empty($newMails)) return;
        debug("notify to dispatcher:".json_encode($newMails));
        $this->processMessager->send(ProcessMessager::DISPATCHER_EMAIL_RECEIVED, json_encode($newMails));
    }
}
