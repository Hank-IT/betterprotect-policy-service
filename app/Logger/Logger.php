<?php

namespace App\Logger;

use Monolog\Logger as MonoLogger;
use Monolog\Handler\StreamHandler;

class Logger
{
    protected $logger = [];

    public function __construct()
    {
        $this->logger['file'] = (new FileLogger(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'betterprotect-policy-service.log'))->getMonolog();
        $this->logger['syslog'] = (new SyslogLogger)->getMonolog();
    }

    public function __get($propertyName)
    {
        if ($propertyName === 'syslog') {
            return $this->logger['syslog'];
        }

        return $this->logger['file'];
    }
}