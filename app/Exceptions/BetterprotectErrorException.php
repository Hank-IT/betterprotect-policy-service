<?php

namespace App\Exceptions;

use Exception;
use App\Logger;

class BetterprotectErrorException extends Exception
{
    protected $action;

    public function __construct(string $message, string $action = 'dunno') {
        parent::__construct($message, 0, null);

        $this->action = $action;

        $logger = new Logger;

        $logger->log($message, LOG_ERR);

        $logger->debug(json_encode($this));
    }

    public function getPostfixAction()
    {
        return $this->action;
    }
}