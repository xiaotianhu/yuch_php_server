<?php 
declare(strict_types=1);
namespace module\event;
/*
 * trigger when new client has connected.
 */
use module\server\ClientEntity;
use module\server\PackageEntity;

class NewBbMessageEvent extends AbstractEvent{
    public ?ClientEntity $client;
    public ?PackageEntity $package;
    public function __construct(?ClientEntity $client, ?PackageEntity $package)
    {
        $this->client = $client;
        $this->package = $package;
    }
}
