<?php 
declare(strict_types=1);
namespace module\server;

use module\server\PackageEntity;
use Serializable;
class BbMessageEntity implements Serializable {
    
    private ?PackageEntity $package; 

    public ?string $id          = null;
    public ?string $version     = null;
    public ?int $mailIndex      = null;
    public ?array $mailFrom     = null;
    public ?array $mailReplyTo  = null;
    public ?array $mailCcTo     = null;
    public ?array $mailBccTo    = null;
    public ?array $mailTo       = null;
    public ?array $group        = null;
    public ?string $subject     = null;
    public ?int $time           = null;
    public ?int $flags          = null;
    public ?string $xMailName   = null;
    public ?string $contain     = null;
    public ?string $containHtml = null;
    public ?int $attachmentNum  = null;
    public ?array $attachments  = [];

    public ?string $filename = "";

    public function __construct(?PackageEntity $package)
    {
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
            'filename'      => $this->filename,
        ];
        return json_encode($d);
    }

    public function unserialize($data)
    {
        $d                   = json_decode($data, true);
        $this->version       = $d['version']??'';
        $this->mailIndex     = $d['mailIndex']??0;
        $this->mailFrom      = $d['mailFrom']??[];
        $this->mailReplyTo   = $d['mailReplyTo']??[];
        $this->mailCcTo      = $d['mailCcTo']??[];
        $this->mailBccTo     = $d['mailBccTo']??[];
        $this->mailTo        = $d['mailTo']??[];
        $this->group         = $d['group']??[];
        $this->subject       = $d['subject']??"";
        $this->time          = $d['time']??0;
        $this->flags         = $d['flags']??0;
        $this->xMailName     = $d['xMailName']??"";
        $this->contain       = $d['contain']??"";
        $this->containHtml   = $d['containHtml']??"";
        $this->attachmentNum = $d['attachmentNum']??0;
        $this->attachments   = $d['attachments']??[];
        $this->filename      = $d['filename']??'';
        return $this;
    }
}
