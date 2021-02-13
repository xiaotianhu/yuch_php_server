<?php 
declare(strict_types=1);
namespace module\server;

use module\server\ClientEntity;
use module\server\PackageEntity;
//use module\exception\ClientException;
use Serializable;
class BbMessageEntity implements Serializable {
    
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

    public function serialize()
    {
        $d           = [
            'version'       => $this->version,
            'mailIndex'     => $this->mailIndex,
            'mailFrom'      => $this->mailFrom,
            'mailReplyTo'   => $this->mailReplyTo,
            'mailCcTo'      => $this->mailCcTo,
            'mailBccTo'     => $this->mailBccTo,
            'mailTo'        => $this->mailTo,
            'group'         => $this->group,
            'subject'       => $this->subject,
            'time'          => $this->time,
            'flags'         => $this->flags,
            'xMailName'     => $this->xMailName,
            'contain'       => $this->contain,
            'containHtml'   => $this->containHtml,
            'attachmentNum' => $this->attachmentNum,
            'attachments'   => $this->attachments, 
        ];
        return json_encode($d);
    }

    public function unserialize($data)
    {
        $d                   = json_decode($data, true);
        $this->version       = $d['version'];
        $this->mailIndex     = $d['mailIndex'];
        $this->mailFrom      = $d['mailFrom'];
        $this->mailReplyTo   = $d['mailReplyTo'];
        $this->mailCcTo      = $d['mailCcTo'];
        $this->mailBccTo     = $d['mailBccTo'];
        $this->mailTo        = $d['mailTo'];
        $this->group         = $d['group'];
        $this->subject       = $d['subject'];
        $this->time          = $d['time'];
        $this->flags         = $d['flags'];
        $this->xMailName     = $d['xMailName'];
        $this->contain       = $d['contain'];
        $this->containHtml   = $d['containHtml'];
        $this->attachmentNum = $d['attachmentNum'];
        $this->attachments   = $d['attachments'];
        return $this;
    }
}
