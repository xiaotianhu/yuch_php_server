<?php
declare(strict_types=1);
namespace module\channel\email\pop3Mailer;

use module\channel\email\pop3Mailer\PopException;

class PopMailer
{
    private string $hostname = "";
    private int $port        = 110;
    private string $username = "";
    private string $password = "";
    private int $timeout     = 5;
    private string $encrypt  = "ssl";
    private string $state    = "DISCONNECTED";
    private $connection = 0;     

    function __construct(string $host, int $port, string $user, string $password, string $encrypt = "ssl", int $timeout = 5)
    {
        $this->hostname = $host;
        $this->port     = $port;
        $this->username = $user;
        $this->password = $password;
        $this->timeout  = $timeout;
        if($encrypt != 'ssl' && $encrypt != 'tls') throw new popException("encrypt protocol err, must be ssl/tls");
        $this->encrypt = $encrypt;
        return $this;
    }

    public function open()
    {
        $conStr = sprintf("%s://%s:%d", $this->encrypt, $this->hostname, $this->port);
        $errNum = $errStr = null;
        $connection = stream_socket_client($conStr, $errNum, $errStr, $this->timeout);
        if(!$connection) throw new PopException($errStr, $errNum);
        $this->connection = $connection;
        stream_set_blocking($this->connection, true);
        $resp = $this->getResp();

        if (substr($resp, 0, 3) != "+OK") {
            throw new PopException("resp err.".$resp);
        }
        $this->state = "AUTHORIZATION";
        return $this;
    }

    private function getResp():?string
    {
        $resp = "";
        while(true){
            if (feof($this->connection)) return "";
            $r = fgets($this->connection, 1);
            if(!empty($r)) $resp .= $r;
            if($r == "\n") return $resp;
        }
    }

    public function login() 
    {
        if($this->state != "AUTHORIZATION") {
            throw new PopException("state must be AUTHORIZATION, state error...".$this->state);
        }

        if (!$this->command("USER ".$this->username, "+OK")){
            throw new PopException("command USER {$this->username} error.");
        }
        if (!$this->command("PASS ".$this->password, "+OK")) {
            throw new PopException("command PASS {$this->password} error.");
        }
        $this->state = "TRANSACTION"; 
        return true;
    }

    public function command(string $command, string $returnOk = ''):string
    {
        if ($this->connection == 0) {
            throw new PopException("no available connection.");
        }
        if (!fputs($this->connection, "$command\r\n")) {
            throw new PopException("send command: $command failed.");
        } 

        $resp = $this->getResp();
        if(empty($returnOk)) return $resp;

        if (substr($resp, 0, strlen($returnOk)) != $returnOk) {
            throw new PopException("command $command returned $resp, error.");
        } 
        return $resp;
    }

    public function stat():array
    {
        if ($this->state != "TRANSACTION") {
            throw new PopException("connection not in TRANSACTION state.");
        }

        $resp = $this->command("STAT", "+OK");
        $r = explode(" ", $resp);
        if(empty($r[1]) || empty($r[2])){
            throw new PopException("STAT command failed.");
        }
        $count = $r[1];
        $size = $r[2];
        return ["mail_count"=>$count, "mail_size"=>$size];
    }

    public function listMail():array
    {
        if ($this->state != "TRANSACTION") {
            throw new PopException("connection not in TRANSACTION state.");
        }
        $resp = $this->command("LIST", "+OK");
        $r = explode("\n", $resp);
        if(empty($r)) throw new PopException("command LIST failed.");

        $list = [];
        for($i = 1;$i <= count($r); $i++){
            if($r[$i] == '.') return $list;

            $l = explode(" ", $r[$i]);
            if(empty($l) || empty($l[1])) continue;
            $list[] = [
                'num' => $l[0],
                'size' => $l[1],
            ]; 
        }
    }

    public function getMail($num = 1, $line = -1):string
    {
        if ($this->state != "TRANSACTION") {
            throw new PopException("connection not in TRANSACTION state.");
        }
        
        if ($line < 0){
            $command = "RETR $num";
        } else{
            $command = "TOP $num $line";
        }

        return $this->command($command, "+OK");
    }

    public function delete(int $num)
    {
        if ($this->state != "TRANSACTION") {
            throw new PopException("connection not in TRANSACTION state.");
        }

        return $this->command("DELE $num ", "+OK");
    }

    public function close()
    {
        $this->command("QUIT", "+OK");
        fclose($this->connection);
        $this->connection = 0;
        $this->state = "DISCONNECTED";
    }
}
