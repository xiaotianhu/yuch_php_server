<?php 
declare(strict_types=1);
namespace module\channel\flomo;

use module\channel\ChannelManager;
use module\channel\BaseChannel;
use module\server\BbMessageEntity;
use module\process\ProcessMessager;
use module\exception\ClientException;

class FlomoChannel extends BaseChannel {

    private ?string $flomoApi = null;
    private ?ProcessMessager $processMessager = null;
    public function __construct()
    {
        $this->processMessager = app()->processMessager;
        $this->flomoApi = config("server.flomo_api");
    }

    public function loop()
    {
        while(true){
            $this->checkMessageQueue();
            sleep(2);
        }
    }

    public function checkMessageQueue():void
    {
        l("flomo channel: checking message queue...");
        $msg = $this->processMessager->receive(ProcessMessager::FLOMO_CHANNEL_SEND_MSG);
        if(!empty($msg) && is_file($msg)){
            $bbMessageEntity = ChannelManager::getCachedEntity($msg);
            $this->sendByChannel($bbMessageEntity);
        }
    }

    public function sendByChannel(BbMessageEntity $bbMessageEntity):bool
    {
        $contain = $bbMessageEntity->contain;
        $title = $bbMessageEntity->subject;
        $content = "#".$title."\r\n".$contain;
        if(empty($this->flomoApi)) throw new ClientException("flomo api not set.");        
        l("send to flomo...".$this->flomoApi);
        $r = curl($this->flomoApi, ["content" => $content]);
        l("send to flomo done,return:".$r);
        return true;
    }
}
