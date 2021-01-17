<?php 
declare(strict_types=1);
namespace module\server;
use module\exception\ServerException;
use module\exception\ClientException;
use Doctrine\Common\EventManager;

class Server{
    public array $configs   = [];
    private $socket         = null;
    private ?array $clients = [];

    private ?Dispatcher $clientDispatcher  = null;
    public  ?EventManager $eventDispatcher = null;

    public function __construct()
    {
        $this->clientDispatcher = new Dispatcher();
        $this->eventDispatcher = new EventManager();
    }

    public function start()
    {
        try{
            $this->init();
            $this->listen();
            while(true){
                $sock = socket_accept($this->socket);
                if(!$sock){
                    $this->waitChild();
                    continue;
                }
                $pid = pcntl_fork();
                if($pid < 0) die("fork failed.");
                if($pid == 0){
                    try{
                        $client = new ClientEntity($sock);
                        $sockId = intval($sock);
                        $this->clients[$sockId] = $client;
                        $this->clientDispatcher->handle($client);
                    }catch(ClientException $e){
                        $this->closeSocket($sock);
                        l($e);
                        exit();
                    }
                }else{
                    l("parent: waiting for children...");
                    $this->waitChild($pid);
                }
            }
        }catch(\Exception $e){
            l($e);
            exit();
        }
    } 

    public function closeSocket($socket)
    {
        l("socket $socket has closed.");
        return socket_close($socket);
    }

    private function init()
    {
        $this->loadConfigs();
        $this->registEventListeners();    
    }

    private function loadConfigs()
    {
        $files = scandir(BASE_DIR."/config");
        foreach($files as $f){
            if($f == "." || $f == "..") continue;
            $file = substr($f, 0, -4);
            $c = require(BASE_DIR."/config/".$f);
            if(empty($c) || !is_array($c)) continue;
            $this->configs[$file] = $c;
        }
    }

    private function listen()
    {
        $host = config("server.host", "0.0.0.0");
        $port = intval(config("server.port", 9999));
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_nonblock($socket);
        socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($socket, $host, $port);
        $l = socket_listen($socket, 10);
        if(!$l){
            $err =  socket_strerror(socket_last_error($socket));
            throw new ServerException($err);
        }
        l("server start listening ".$host."@".$port);
        $this->socket = $socket;
    }

    private function registEventListeners()
    {
        $listeners = config("listeners");
        if(empty($listeners)) return;
        foreach($listeners as $event => $listener){
            if(!class_exists($event)) throw new ServerException("Event: {$event} not exist, registEventListener failed");
            if(!class_exists($listener)) throw new ServerException("Listener: {$listener} not exist, registEventListener failed");
            $this->eventDispatcher->addEventListener($event, (new $listener()));
        }
    }

    private function registSignalHandler()
    {

    }

    private function waitChild()
    {
        $exit = null;
        $r = pcntl_wait($exit, WNOHANG);
        if($r == -1){
            //nothing.
        }else{
            l("child has exited.".$exit);
        }
        sleep(1);
    }
    
}
