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

    }

    private function initAllChannel(array $channels)
    {
        foreach($channels as $channel){
            $pid = pcntl_fork();
            if($pid > 0){
                $this->channelPids[$channel] = $pid;
            }
            if($pid ==0){
                (new $channel())->daemon();
            }
        }
        //$exit = null;
        //pcntl_wait($exit, WNOHANG);
    }

    private function selectChannel(BbMessageEntity $msg)
    {

    }
}
    
