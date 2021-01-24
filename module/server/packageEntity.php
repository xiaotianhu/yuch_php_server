<?php 
declare(strict_types=1);
namespace module\server;

class PackageEntity {
    use DataTrait;

    private ?array $data = [];

    public function __construct(?array $data)
    {
        $this->data = $data;
    }

    public function readByte()
    {
        $b = $this->slice($this->data, 1);
        if(isset($b[0])) return $b[0];
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

    public function readStringArray():?array
    {
        $len = $this->readInt();
        if($len < 1) return [];
        $r = [];
        for($i=0;$i<$len;$i++){
            $r[] = $this->readString(); 
        }
        return $r;
    }

    public function readLong():?int
    {
        $low  = $this->readInt();
        $high = $this->readInt();
        if($low >= 0){
            return (($high << 32) | $low);
        }else{
            return (($high<< 32) | ((($low & 0x7fffffff)) | 0x80000000));
        }
    }

}

