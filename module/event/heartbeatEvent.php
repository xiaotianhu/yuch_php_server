<?php 
declare(strict_types=1);
namespace module\event;
/*
 * trigger when new client has connected.
 */
use module\server\ClientEntity;
use module\server\PackageEntity;

class HeartbeatEvent extends AbstractEvent{
    public ?ClientEntity $clientEntity = null;
    public ?PackageEntity $package = null;

    public function __construct(?ClientEntity $client, PackageEntity $package)
    {
        $this->clientEntity = $client;
        $this->package      = $package;
    }
}
