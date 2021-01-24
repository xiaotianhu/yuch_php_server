<?php 
declare(strict_types=1);
namespace module\server;

use module\server\ClientEntity;
use module\server\PackageEntity;
use module\exception\ClientException;
class EmailEntity {
    
    private ?ClientEntity $client;
    private ?PackageEntity $package; 

    public $version       = null;
    public $mailIndex     = null;
    public $mailFrom      = null;
    public $mailReplyTo   = null;
    public $mailCcTo      = null;
    public $mailBccTo     = null;
    public $mailTo        = null;
    public $group         = null;
    public $subject       = null;
    public $time          = null;
    public $flags         = null;
    public $xMailName     = null;
    public $contain       = null;
    public $containHtml   = null;
    public $attachmentNum = null;
    public $attachments   = [];

    public function __construct(ClientEntity $client, PackageEntity $package)
    {
        $this->client = $client;
        $this->package = $package; 
    }

    public function parseFromPackage()
    {
        $p                   = $this->package;
        $this->version       = $p->readByte();
        $this->mailIndex     = $p->readInt();
        $this->mailFrom      = $p->readStringArray();
        $this->mailReplyTo   = $p->readStringArray();
        $this->mailCcTo      = $p->readStringArray();
        $this->mailBccTo     = $p->readStringArray();
        $this->mailTo        = $p->readStringArray();
        $this->group         = $p->readStringArray();
        $this->subject       = $p->readString();
        $this->time          = $p->readLong();
        $this->flags         = $p->readInt();
        $this->xMailName     = $p->readString();
        $this->contain       = $p->readString();
        $this->containHtml   = $p->readString();
        $this->attachmentNum = $p->readInt();
    }
}
