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
            if(!$p instanceof PackageEntity){

                echo "unhandlePackage not instanceof packageEntity.";
                var_dump($p);

                return null;
            }
            return $p;
        }

        try{
            $d   = $this->socketRead(4);
            $int = $this->parseInt($d);

            echo "socket read4 for parseint";
            var_dump($d);
        }catch(ClientException $e){
            l("socket parse int failed.dumping socket data:");
            return null;
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
            
            echo 'unziped data:\r\n';
            var_dump($data);
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
        if(count($buffer) != 4){
            l("parse int wrong, buffer not full...");var_dump($buffer);
            if(empty($buffer[3])) $buffer[3] = 0;
            if(empty($buffer[2])) $buffer[2] = 0;
            if(empty($buffer[1])) $buffer[1] = 0;
            if(empty($buffer[0])) $buffer[0] = 0;
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
        if(!$data) return null;
        $dataLen = count($data);
        if(empty($data) || $dataLen == 0 || !$data) {
            l("parse empty package, return null;\r\n");
            return null;
        }
        $tlen    = $this->parseInt($this->slice($data, 4));
        $package = $this->slice($data, $tlen);
        $tlen += 4;
        while($tlen < $dataLen){
            $ntlen = $this->parseInt($this->slice($data, 4));
            $npackage = $this->slice($data, $ntlen);
            $tlen += 4;
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

    public function writeString(&$outPackage, string $str){
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
        debug("send to bb:  $sentLen \r\n");

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

