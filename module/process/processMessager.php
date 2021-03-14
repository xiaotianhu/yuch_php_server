<?php 
declare(strict_types=1);
namespace module\process;

/*
 * message queue for communicate between processes
 */
use module\exception\ServerException;
class ProcessMessager{

    //channel types
    const EMAIL_CHANNEL_SEND_MSG = 1;
    const DISPATCHER_EMAIL_RECEIVED = 2;

    private $_queue = null;
    
    public function __construct()
    {
        if(!$this->_queue){
            if(!is_file(BASE_DIR.'/runtime/queue')) file_put_contents(BASE_DIR.'/runtime/queue', "");
            $tokId = ftok(BASE_DIR.'/runtime/queue', 'G');
            $this->_queue = msg_get_queue($tokId, 0666);
        }
    }

    public function send(int $type, string $message):void
    {
        debug("push message into msg_queue msg:$message \r\n type:{$type}");
        $s = msg_send($this->_queue, $type, $message, false);
        if(!$s) throw new ServerException("send message with ProcessMessageService failed.");
    }

    /*
     * non-blocking, return null if there is no message. 
     */
    public function receive(int $type):?string
    {
        $msg = null;
        $msgType = null;
        $rec = msg_receive($this->_queue, $type, $msgType, 65535, $msg, false, MSG_IPC_NOWAIT);

        if($rec) {
            debug("received message from msg_queue msg:$msg \r\n type:{$type}");
            return $msg;
        }
        return null;
    }

    public static function getAllDispatcherTypes()
    {
        return [
            self::DISPATCHER_EMAIL_RECEIVED,
        ];
    }
    
    /*
     * nonblock check if the queue has unhandled messages
     * @return bool true:has new messages
    public function hasMessage():bool
    {
        $stat = msg_stat_queue($this->_queue);
        if(empty($stat)||empty($stat['msg_qnum'])) throw new ServerException("process message queue stat failed.");
        if($stat['msg_qnum'] == 0) return false;

        return true; 
    }
     */
}
