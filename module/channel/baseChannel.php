<?php 
declare(strict_types=1);
namespace module\channel;
use module\server\BbMessageEntity;

abstract class BaseChannel{

    abstract public function loop();
    abstract public function checkMessageQueue():void;
}
