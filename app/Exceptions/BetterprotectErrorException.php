<?php

namespace App\Exceptions;

use Exception;

class BetterprotectErrorException extends Exception
{
    protected $action;

    public function __construct(string $message, string $action = 'dunno') {
        parent::__construct($message, 0, null);

        $this->action = $action;
    }

    public function getPostfixAction()
    {
        return $this->action;
    }
}