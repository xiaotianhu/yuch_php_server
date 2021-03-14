<?php 
declare(strict_types=1);
namespace module\listener;

use module\event\SendToBbEvent;
use module\channel\email\EmailChannel;
class SendToBbListener extends AbstractListener{
    private ?SendToBbEvent $event = null;
    
    public function handle($event):void
    {
        $this->event = $event;
        $this->clearUnreadEmail();
    }

    private function clearUnreadEmail()
    {
        if(empty($this->event->messageEntity)) return;
        $file = $this->event->messageEntity->filename;
        if(empty($file)) return;
        
        EmailChannel::removeSavedEmail($file);
        debug("unread email removed: $file "); 
    }
}
