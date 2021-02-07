<?php 
declare(strict_types=1);
namespace module\channel\email;

use module\channel\BaseChannel;
use module\server\BbMessageEntity;
use module\process\ProcessMessager;

class EmailChannel extends BaseChannel {
    
    private ?ProcessMessager $processMessager = null;
    public function __construct()
    {
        $this->processMessager = app()->processMessager;
        var_dump($this->processMessager);
    }

    public function daemon():bool
    {
        return true;
    }

    public function checkNewMessage():array
    {
        return [];
    }

    public function send(BbMessageEntity $bbMessageEntity):bool
    {
        return true;
    }
}
