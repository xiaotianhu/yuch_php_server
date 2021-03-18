<?php 
declare(strict_types=1);
namespace module\server;
use module\server\PackageEntity;
use module\exception\ClientException;

trait DataTrait{
    /*
     * @var Resource of socket connection
     */
    private $socket          = null;
    private $unhandlePackage = [];
    private $unsendPackage   = [];

    
    public function receivePackage():?PackageEntity
    {
        if(!empty($this->unhandlePackage)){
            $p = array_shift($this->unhandlePackage);
            return $p;
        }

        try{
            $d   = $this->socketRead(4);
            $int = $this->parseInt($d);
        }catch(ClientException $e){
            l("socket parse int failed.dumping socket data:");
            throw new ClientException("parse package head int failed.");
        }
        if($int < 0) throw new ClientException("read int err.");

        $zipLen = 0;
        $orgLen = 0;
        if($int == 0){
            $orgLen = $this->parseInt($this->socketRead(4));
            $zipLen = $this->parseInt($this->socketRead(4));
        }else{
             $zipLen = $int & 0x0000ffff;
             $orgLen = $int >> 16;
        }

        if($zipLen == 0){
            $data = $this->socketRead($orgLen);
            l("nonzip data received. orglen: $orgLen ");
        }else{
            l("zip data received. zipLen: $zipLen orglen: $orgLen");
            $zipData = $this->socketRead($zipLen);
            $data    = $this->unzipDataArray($zipData);
        }
        return $this->parsePackage($data);
    }


    /*
     * read from socket
     */
    public function socketRead($len):array
    {
        if($len < 1) return null;
        $data = [];
        for($i=0;$i<$len;$i++){
            if(!$this->socket) throw new ClientException("client socket err.");
            $d = null;
            $res = socket_recv($this->socket, $d, 1, MSG_WAITALL);
            if($res === 0) throw new ClientException("connection closed.");
            if(!$res) return [];
            
            $r = ord($d);
            //debug("socketRead-read origin: {$d}  ord: {$r}");
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

    public function parseInt($buffer=null):int
    {
        if(empty($buffer)){
            throw new ClientException("err parse Int,empty buf or buf length != 4.".json_encode($buffer));
        }

        $r = $buffer[0] | ($buffer[1] << 8) | ($buffer[2]  << 16) | ($buffer[3] << 24);
        return intval($r);
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

    private function parsePackage($data):?PackageEntity
    {
        //l("begin parse package data:");var_dump($data);
        if(!$data) return null;
        $dataLen = count($data);
        if(empty($data) || $dataLen == 0 || !$data) {
            l("parse empty package, return null;\r\n");
            return null;
        }
        $tlen    = $this->parseInt($this->slice($data, 4));//包体长
        $package = $this->slice($data, $tlen);
        $tlen += 4;//包体长+包头4位 跳过当前这个包 到下一个包
        while($tlen < $dataLen){
            $ntlen = $this->parseInt($this->slice($data, 4));
            $npackage = $this->slice($data, $ntlen);
            $tlen += $ntlen+4;
            $this->unhandlePackage[] = new PackageEntity($npackage);
        }
        return (new PackageEntity($package));
    }

    public function writeByte(&$outPackage, int $ascii){
        $outPackage .= chr($ascii);
    }

    public function writeInt(&$outPackage, int $int){
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

    public function writeString(&$outPackage, ?string $str=""){
        if(empty($str) || strlen($str) == 0){
            return $this->writeInt($outPackage, 0);
        }
        $str    = str_replace('\0', '', $str);
        $strLen = strlen($str);
        $this->writeInt($outPackage, $strLen);
        $outPackage .= $str;
    }

    public function writeStringArray(&$outPackage, array $strArray){
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
            //send gzip data
            $header = ((strlen($sendBuffer)<<16) & 0xffff0000)|strlen($zipSendBuffer);
            $outBuf = "";
            $this->writeInt($outBuf, $header);
            $sendBuffer = $outBuf.$zipSendBuffer;
            $length = strlen($sendBuffer);
            debug("send zip. orglen:".strlen($sendBuffer)."ziplen:".strlen($zipSendBuffer));
        }
        $sentLen = socket_write($this->socket, $sendBuffer);

        //for test use
        if(getenv("DEBUG")){
            $test = [];
            foreach(str_split($sendBuffer) as $v){
                $test[] = ord($v);
            }
        }
        debug("unsend buffer length：  $length \r\n");
        debug("send to bb length:  $sentLen \r\n");

        return $this;
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
        $this->unsendPackage = [];
        return $sendBuffer;
    }
}

