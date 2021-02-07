<?php 
declare(strict_types=1);
namespace module\channel;
use module\server\BbMessageEntity;

abstract class BaseChannel{

    abstract public function daemon():bool;

    /*
     * Check new message and return array of BbMessageEntity
     * @return []BbMessageEntity
     */
    abstract public function checkNewMessage():array;

    abstract public function send(BbMessageEntity $bbMessageEntity):bool;
}
