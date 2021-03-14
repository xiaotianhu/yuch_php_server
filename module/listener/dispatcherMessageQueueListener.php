<?php 
declare(strict_types=1);
namespace module\listener;

use module\event\DispatcherMessageQueueEvent;
use module\process\ProcessMessager;
use module\channel\email\EmailChannel;
class DispatcherMessageQueueListener extends AbstractListener{
    private ?DispatcherMessageQueueEvent $event = null;
    
    public function handle($event):void
    {
        $this->event = $event;
        switch($this->event->messageType){
            case ProcessMessager::DISPATCHER_EMAIL_RECEIVED:
                $this->emailReceived();
                break;
            
            default:
            break;
        }
    }

    /*
     * received new email, send to bb.
     */
    private function emailReceived()
    {
        $emails = json_decode($this->event->message, true);
        if(empty($emails)) return;
        foreach($emails as $file){
            try{
                $entity = EmailChannel::loadUnreadFileToMessageEntity($file);
                if(empty($entity)) continue;
                $this->event->clientEntity->sendToBb($entity);
            }catch(\Throwable $e){
                l($e->getMessage());
                l($e->getFile());
                l($e->getLine());
            }
        }
    }
}


