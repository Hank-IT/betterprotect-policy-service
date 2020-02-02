<?php

namespace App\Logger;

use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonoLogger;

class FileLogger implements LoggerInterface
{
    protected $path;

    public function __construct($path)
    {
        $this->path = $path;
    }

    public function getMonolog()
    {
        $log = new MonoLogger('betterprotect');

        $log->pushHandler(new StreamHandler($this->path));

        return $log;
    }
}