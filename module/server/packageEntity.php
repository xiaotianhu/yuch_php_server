<?php 
declare(strict_types=1);
namespace module\server;

class packageEntity {
    use DataTrait;

    private ?array $data = [];

    public function __construct(?array $data)
    {
        $this->data = $data;
    }

    public function readByte()
    {
        $b = $this->slice($this->data, 1);
        if(!empty($b[0])) return $b[0];
        return null;
    }

    public function readInt():int
    {
        return $this->parseInt($this->slice($this->data, 4));
    }

    public function readString():string
    {
        $strLen = $this->readInt();
        if($strLen <= 0) return "";

        $strArr = $this->slice($this->data, $strLen);
        if(empty($strArr)) return "";

        $str = "";
        foreach($strArr as $v){
            $str .= chr($v);
        }
        return $str;
    }

}

