<?php

namespace App;

class Logger
{
    public function log(string $line, $priority = LOG_INFO)
    {
        openlog('syslog', LOG_PID, LOG_MAIL);
        syslog($priority, 'Betterprotect Policy Server: ' . $line);
        closelog();
    }

    public function debug(string $line)
    {
        file_put_contents(__DIR__ . '/../storage/debug.log', $line . "\n", FILE_APPEND);
    }
}