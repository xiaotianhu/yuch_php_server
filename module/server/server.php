<?php 
declare(strict_types=1);
namespace module\server;
use module\exception\ServerException;
use Doctrine\Common\EventManager;

class Server{
    private $socket = null;
    private ?array $clients = [];
    private ?Dispatcher $clientDispatcher = null;
    private ?EventManager $eventDispatcher = null;

    public function __construct()
    {
        $this->clientDispatcher = new Dispatcher();
        $this->eventDispatcher = new EventManager();
    }

    public function start()
    {
        $this->init();
        $this->listen();
        while(true){
            $sock = socket_accept($this->socket);
            $pid = pcntl_fork();
            if($pid < 0) die("fork failed.");
            if($pid == 0){
                $client = new ClientEntity($sock);
                $this[$sock] = $client;
                $this->clientDispatcher->handle($client);
            }
            sleep(1); 
        }
    } 

    private function init()
    {
        
    }


    private function listen()
    {
        $host = config("server.host", "0.0.0.0");
        $port = config("server.port", 9999);
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
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


}
