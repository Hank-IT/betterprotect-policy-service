<?php

namespace App\Logger;

use Monolog\Handler\SyslogHandler;
use Monolog\Logger as MonoLogger;

class SyslogLogger implements LoggerInterface
{
    public function getMonolog()
    {
        $log = new MonoLogger('betterprotect');

        $log->pushHandler(new SyslogHandler('betterprotect/policy-service', LOG_MAIL));

        return $log;
    }
}