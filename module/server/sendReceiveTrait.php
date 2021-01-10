<?php 
declare(strict_types=1);
namespace module\server;
use module\exception\ClientException;

trait SendReceiveTrait{
    private ?array $package = [];
    private ?array $sendBuffer = null;

    public function receivePackage()
    {
        if(!empty($this->package)){
            return array_unshift($this->package);
        }
        $int = $this->parseInt($this->read(4));
        if($int < 0) throw new \Exception("read int err.");

        $zipLen = 0;
        $orgLen = 0;
        if($int == 0){
            $orgLen = $this->parseInt($this->read(4));
            $zipLen = $this->parseInt($this->read(4));
        }else{
             $zipLen = $int & 0x0000ffff;
             $orgLen = $int >> 16;
        }

        if($zipLen == 0){
            $data = $this->read($orgLen);
            l("nonzip data received. orglen: $orgLen ");
        }else{
            l("zip data received. zipLen: $zipLen orglen: $orgLen");
            $zipData = $this->read($zipLen);
            $data = $this->unzipDataArray($zipData);
        }
        return $this->parsePackage($data);
    }

    public function read($len){
        if($len < 1) return null;
        $data = [];
        for($i=0;$i<$len;$i++){
            $r = ord(socket_read($this->socket, 1));
            $data[] = $r;
        }
        return $data;
    }

    public function slice(array &$arr, $length=false):array
    {
        if(empty($arr) || !is_array($arr)) return [null];
        $newData = array_slice($arr, 0, $length);
        $arr = array_slice($arr, $length);
        if(!$newData || count($newData) == 0) $newData[] = null;
        return $newData;
    }


    public function parseInt(&$buffer=null){
        if(empty($buffer) || count($buffer) != 4){
            throw new ClientException("err parse Int.");
        }

        return $buffer[0] | ($buffer[1] << 8) | ($buffer[2]  << 16) | ($buffer[3] << 24);
    }

    public function parseLong(&$buffer=null){
        $low  = $this->parseInt($this->slice($buffer, 4));
        $high = $this->parseInt($this->slice($buffer, 4));
        if($low >= 0){
            return (($high << 32) | $low);
        }else{
            return (($high<< 32) | ((($low & 0x7fffffff)) | 0x80000000));
        }

    }

    public function parseString(&$buffer){
        if(empty($buffer)||count($buffer) < 4)
            return "";
        $length = $this->parseInt($this->slice($buffer, 4));
        $strArr = $this->slice($buffer, $length);
        $str = "";
        foreach($strArr as $v){
            $str .= chr($v);
        }
        return $str;
    }

    public function parseStringArray(&$buffer){
        if(empty($buffer)||count($buffer) < 4)
            return "";
        $len = $this->parseInt($this->slice($buffer, 4));
        $r = [];
        for($i=0; $i<$len; $i++){
            $r[] = $this->parseString($buffer);
        }
        return $r;
    }


    public function writeByte(&$outPackage, $str){
        $outPackage .= chr($str);
    }

    public function writeInt(&$outPackage, $int){
        $outPackage .= pack("V*", $int);
    }

    public function writeLong(&$outPackage, $long){
        $outPackage .= pack("P*", $long);
    }

    public function writeBool(&$outPackage, $bool){
        if($bool) {
            $outPackage .= chr(1);
        }else{
            $outPackage .= chr(0);
        }
    }

    public function writeString(&$outPackage, $str){
        if(empty($str) || strlen($str) == 0){
            return $this->writeInt($outPackage, 0);
        }
        $str    = str_replace('\0', '', $str);
        $strLen = strlen($str);
        $this->writeInt($outPackage, $strLen);
        $outPackage .= $str;
    }

    public function writeStringArray(&$outPackage, $strArray){
        $len = count($strArray);
        $this->writeInt($outPackage, $len);
        foreach($strArray as $v){
            $this->writeString($outPackage, $v);
        }
    }

    public function addPackageToBuffer($package){
        $this->unsendPackage[] = $package;
    }

    public function sendToClient(){
        $sendBuffer    = $this->packPackage();
        $zipSendBuffer = zlib_encode($sendBuffer, ZLIB_ENCODING_GZIP);

        if(strlen($zipSendBuffer) > strlen($sendBuffer)){
            $header = (strlen($sendBuffer)<<16) & 0xffff0000;
            $outBuf = "";
            $this->writeInt($outBuf, $header);
            $sendBuffer = $outBuf.$sendBuffer;
            $length = strlen($sendBuffer);
        }else{
            //发送gzip后的数据
            $header = ((strlen($sendBuffer)<<16) & 0xffff0000)|strlen($zipSendBuffer);
            $outBuf = "";
            $this->writeInt($outBuf, $header);
            $sendBuffer = $outBuf.$zipSendBuffer;
            $length = strlen($sendBuffer);
            l("send zip. orglen:".strlen($sendBuffer)."ziplen:".strlen($zipSendBuffer));
        }
        $sentLen = socket_write($this->socket, $sendBuffer);

        //for test use
        $test = [];
        foreach(str_split($sendBuffer) as $v){
            $test[] = ord($v);
        }
        l("unsend buffer 长：  $length \r\n");
        l("发送:  $sentLen \r\n");

        return $this;
    }


    private function parsePackage($data){
        if(!$data) return null;
        $dataLen = count($data);
        if(empty($data) || $dataLen == 0 || !$data) {
            l("parse empty package, return null;\r\n");
            return null;
        }
        $tlen = $this->parseInt($this->slice($data, 4));
        $package = $this->slice($data, $tlen);
        $tlen += 4;
        while($tlen < $dataLen){
            $ntlen = $this->parseInt($this->slice($data, 4));
            $npackage = $this->slice($data, $ntlen);
            $tlen += 4;
            $this->unhandelPackage[] = $npackage;
        }
        return $package;
    }

    private function unzipDataArray($zipArrayByte){
        $zipStr = "";
        foreach($zipArrayByte as $v){
            $zipStr .= chr($v);
        }
        $orgStr = zlib_decode($zipStr);
        $orgStrByteArray = [];
        foreach(str_split($orgStr) as $v){
            $orgStrByteArray[] = ord($v);
        }
        return $orgStrByteArray;
    }

    private function packPackage(){
        if(empty($this->unsendPackage)) return null;
        $sendBuffer = "";
        foreach($this->unsendPackage as $p){
            $len = strlen($p);
            $sendBuffer .= pack("V*", $len);
            $sendBuffer .= $p;
        }
        $this->sendBuffer = [];
        return $sendBuffer;
    }



}
