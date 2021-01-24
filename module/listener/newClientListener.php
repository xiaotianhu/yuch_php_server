<?php 
declare(strict_types=1);
namespace module\listener;

class NewClientListener extends AbstractListener{
    
    public function handle($event):void
    {
        l("new client listener on handle.");
        var_dump($event);
    }
}


