<?php 
declare(strict_types=1);
namespace module\server;
use module\server\PackageEntity;
use module\exception\ClientException;

trait DataTrait{
    /*
     * @var Resource of socket connection
     */
    private $socket = null;
    private $unhandlePackage = [];

    
    public function receivePackage():PackageEntity
    {
        if(!empty($this->unhandlePackage)){
            return array_unshift($this->unhandlePackage);
        }

        $int = $this->parseInt($this->socketRead(4));
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
        if(empty($buffer) || count($buffer) != 4){
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
}

