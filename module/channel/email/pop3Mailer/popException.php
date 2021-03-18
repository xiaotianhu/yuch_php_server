<?php
declare(strict_types=1);
namespace module\channel\email\pop3Mailer;

use Exception;
class PopException extends Exception{
    public function __construct($message, $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }    
}
