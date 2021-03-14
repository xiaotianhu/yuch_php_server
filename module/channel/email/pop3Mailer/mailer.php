<?php
declare(strict_types=1);
namespace module\channel\email\pop3Mailer;
use ZBateson\MailMimeParser\MailMimeParser;
use ZBateson\MailMimeParser\Header\HeaderConsts;

class Mailer {
    private ?string $runtimePath = null; 
    private ?PopMailer $popMailer = null;
    private ?int $maxUnread = null;

    const MAILER_PATH = "/mailer";

    //private $smtpMailer = null;
    /*
     * @param $config [
     * "pop3_host" => "pop3.mail.com",
     * "pop3_port" => 995,
     * "smtp_host" => "smtp.mail.com",
     * "smtp_port" => "465,
     * "username"  => "mail_account",
     * "password"  => "mail_password",
     * "encrypt"   => "ssl" or "tls",
     * "runtime_path" => "/path/to/runtime"
     * ]
     */
    public function __construct(array $config)
    {
        $this->popMailer = new PopMailer(
            $config['pop3_host'],
            $config['pop3_port'],
            $config['username'],
            $config['password'],
            $config['encrypt'],
        );
        $this->runtimePath  = $config["runtime_path"];
        $this->maxUnread    = intval($config["max_unread"])??20;
        //$this->smtpMailer = "";
        $this->init();
    }

    private function init()
    {
        if(!is_dir($this->runtimePath)) {
            throw new MailerException("runtime path {$this->runtimePath} not exist.");
        }
        $this->runtimePath = $this->runtimePath.self::MAILER_PATH;
        if(!is_dir($this->runtimePath)){
            mkdir($this->runtimePath);
        }

        $this->popMailer->reconnect();
    }

    public function checkUnread():?array
    {
        $list = $this->popMailer->listMail();
        $listId = array_reduce($list, function($carry, $i){
            if(empty($i["num"])) return $carry;
            $carry[] = $i["num"];
            return $carry;
        }, []);
        $unreadCacheFile = $this->runtimePath."/unreadlog.json";
        $unreadList = [];
        if(!is_file($unreadCacheFile)){
            $unreadList = array_slice($listId, -($this->maxUnread), $this->maxUnread);
        }else{
            $cacheFile  = json_decode(file_get_contents($unreadCacheFile), true);
            $cacheList  = $cacheFile["list"];
            $nowList    = array_slice($listId, -($this->maxUnread), $this->maxUnread);
            $unreadList = array_diff($nowList, $cacheList);
        }

        //refresh log
        $cache              = [
            "last_check_at" => time(),
            "list"          => array_slice($listId, -($this->maxUnread), $this->maxUnread),
        ];
        file_put_contents($unreadCacheFile, json_encode($cache));
        debug("latest mail id is: ".array_pop($listId));
        
        //list only refresh on next connetion.
        $this->popMailer->reconnect();
        return $unreadList;
    }

    /*
     * download unread emails from server,parse and store in the runtime path
     * @param $ids array of unread email ids eg:[1,2,3 ...]
     */
    public function retrieveUnreads(array $ids):?array
    {
        $parsedMails = [];
        foreach($ids as $id)
        {
            $mailOrigin    = $this->popMailer->getMail($id, 65535);
            $parser        = new MailMimeParser();
            $parsedMail    = $parser->parse($mailOrigin);
            $parsedMails[] = $this->parseMailToArray(intval($id), $parsedMail);
        }
        return $parsedMails;
    }
    private function parseMailToArray(int $id, $parsedMail):?array
    {
        if(empty($id) || empty($parsedMail)) return null;
        $result            = [
            "id"           => $id,
            "from"         => $parsedMail->getHeaderValue(HeaderConsts::FROM),
            "to"           => $parsedMail->getHeaderValue(HeaderConsts::TO),
            "cc"           => $parsedMail->getHeaderValue(HeaderConsts::CC),
            "bcc"          => $parsedMail->getHeaderValue(HeaderConsts::BCC),
            "message_id"   => $parsedMail->getHeaderValue(HeaderConsts::MESSAGE_ID),
            "reply_to"     => $parsedMail->getHeaderValue(HeaderConsts::REPLY_TO),
            "content_type" => $parsedMail->getHeaderValue(HeaderConsts::CONTENT_TYPE),
            "text_content" => $parsedMail->getTextContent(),
            "html_content" => $parsedMail->getHtmlContent(),
        ];
        $attachments = $parsedMail->getAllAttachmentParts();
        foreach($attachments as $key=>$attachment){
            if(empty($attachment)) continue;
            $filename = $attachment->getHeaderParameter(
                'Content-Type',
                'name',
                $attachment->getHeaderParameter(
                    'Content-Disposition',
                    'filename',
                    '__unknown_file_name_' . $key
                )
            );
            if(!is_dir("/tmp/mailer/{$id}")) mkdir("/tmp/mailer/{$id}", 0777, true);
            $attachment->saveContent("/tmp/mailer/{$id}/{$filename}");
            $result['attachments'][$filename] = "/tmp/mailer/{$id}/{$filename}";
        }
        return $result;
    }
}
