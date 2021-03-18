<?php 
declare(strict_types=1);
namespace module\channel;

use module\exception\ClientException;
use module\server\BbMessageEntity;
use module\process\ProcessMessager;
use module\channel\email\EmailChannel;
use module\channel\flomo\FlomoChannel;
class ChannelManager {

    private ?ProcessMessager $processMessager = null;
    private ?array $channelPids = [];

    public function __construct()
    {
        $this->processMessager = app()->processMessager;

        $channels = [
            EmailChannel::class,
            FlomoChannel::class,
        ];
        $this->initAllChannel($channels);
    }

    public function sendByChannel(BbMessageEntity $bbMessageEntity)
    {
        $file = self::cacheEntity($bbMessageEntity);
        $type = $this->selectChannelType($bbMessageEntity);
        $this->processMessager->send($type, $file); 
    }

    /*
     * @return $file full path of cached file
     */
    public static function cacheEntity(BbMessageEntity $bbMessageEntity):string
    {
        l("channelmanager:cache by channel....".serialize($bbMessageEntity));
        $dir = BASE_DIR."/runtime/message/";
        if(!is_dir($dir)) mkdir($dir);
        $file = intval(microtime(true)).".json";
        file_put_contents($dir.$file, serialize($bbMessageEntity));
        return $dir.$file;
    }

    public static function getCachedEntity(string $file):?BbMessageEntity
    {
        if(!is_file($file)) return null;
        $fileContent = file_get_contents($file);
        l("get cached entity \r\n\r\n");
        $entity = unserialize($fileContent);
        return $entity;
    }

    private function initAllChannel(array $channels)
    {
        foreach($channels as $channel){
            $pid = pcntl_fork();
            if($pid > 0){
                $this->channelPids[$channel] = $pid;
            }
            if($pid == 0){
                l("start new channel process:".$channel);
                (new $channel())->loop();
            }
        }
    }

    /*
     * @return type of the channel
     */
    private function selectChannelType(BbMessageEntity $msg):int
    {
        $channels = config("channel");
        
        if(empty($msg->getMailToEmail())){
            throw new ClientException("mail to user not found.");
        }
        $toMails = $msg->getMailToEmail();
        $toUser = array_shift($toMails);
        $ch = null;
        foreach($channels as $channel => $emails){
            if($ch === null) $ch = $channel;//first will be default;
            if(in_array($toUser, $emails)) $ch = $channel;
        }
        debug("selected channel is: {$ch}");
        return $ch;
    }
}
    
