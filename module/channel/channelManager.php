<?php 
declare(strict_types=1);
namespace module\channel;

use module\server\BbMessageEntity;
use module\process\ProcessMessager;
use module\channel\email\EmailChannel;
class ChannelManager {

    private ?ProcessMessager $processMessager = null;
    private ?array $channelPids = [];

    public function __construct()
    {
        $this->processMessager = app()->processMessager;

        $channels = [
            EmailChannel::class,
        ];
        $this->initAllChannel($channels);
    }

    public function sendByChannel(BbMessageEntity $bbMessageEntity)
    {
        l("channelmanager:send by channel....".serialize($bbMessageEntity));
        $dir = BASE_DIR."/runtime/message/";
        if(!is_dir($dir)) mkdir($dir);
        $file = intval(microtime(true)).".json";
        file_put_contents($dir.$file, serialize($bbMessageEntity));
        $type = $this->selectChannelType($bbMessageEntity);
        $this->processMessager->send($type, $dir.$file); 
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
        return EmailChannel::MESSAGE_TYPE;
    }
}
    
