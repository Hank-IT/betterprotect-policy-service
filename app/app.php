<?php

require __DIR__.'/../vendor/autoload.php';

use App\Responder;
use App\Logger\Logger;
use App\RequestHandler;
use App\Exceptions\BetterprotectErrorException;

$f = fopen( 'php://stdin', 'r' );

while($line = fgets($f)) {
    // Match and store options
    if(preg_match("/([^=]+)=(.*)\n/", $line)) {
        $line = explode('=', $line);

        $options[$line[0]] = str_replace("\n", '', $line[1]);
    } else {
        // Break loop
        fclose($f);

        try {
            if (empty($options)) {
                throw new BetterprotectErrorException('No options received from postfix.', RequestHandler::POSTFIX_ACTION_DEFER);
            }

            if ($options['request'] !== 'smtpd_access_policy') {
                throw new BetterprotectErrorException('Unknown request type.', RequestHandler::POSTFIX_ACTION_DEFER);
            }

            $action = (new RequestHandler($options))->getResponse();
        } catch (Throwable $exception) {
            if (method_exists($exception, 'getPostfixAction')) {
                $action = $exception->getPostfixAction();
            } else {
                $action = RequestHandler::POSTFIX_ACTION_DEFER;
            }

            (new Logger)->file->info($exception->getMessage(), (array) $exception);
        }

        (new Logger)->syslog->info('Decision: ' . $action);

        print (new Responder($action))->respond();

        exit(0);
    }
}
