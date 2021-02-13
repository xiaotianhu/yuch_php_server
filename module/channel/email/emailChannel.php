<?php 
declare(strict_types=1);
namespace module\channel\email;

use module\channel\BaseChannel;
use module\server\BbMessageEntity;
use module\process\ProcessMessager;

class EmailChannel extends BaseChannel {
    /*
     * type for message queue
     */
    const MESSAGE_TYPE = 1;
    
    private ?ProcessMessager $processMessager = null;
    public function __construct()
    {
        $this->processMessager = app()->processMessager;
    }

    public function loop()
    {
        while(true){
            $this->checkMessageQueue();
            sleep(1);
        }
    }


    public function checkMessageQueue():void
    {
        l("email channel: checking message queue...");
        $msg = $this->processMessager->receive(self::MESSAGE_TYPE);
        if($msg) var_dump($msg);
    }

    public function sendByChannel(BbMessageEntity $bbMessageEntity):bool
    {
        return true;
    }
}
