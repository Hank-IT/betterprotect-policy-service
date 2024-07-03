<?php

require __DIR__.'/../vendor/autoload.php';

use App\Responder;
use App\Logger\Logger;
use App\Database\MySQL;
use App\RequestHandler;
use App\Support\Config;
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

            $database = (new MySQL((new Config)->readConfig()['database']))->boot();

            $action = (new RequestHandler($options, $database, new Logger))->getResponse();
        } catch (Throwable $exception) {
            if (method_exists($exception, 'getPostfixAction')) {
                $action = $exception->getPostfixAction();
            } else {
                $action = RequestHandler::POSTFIX_ACTION_DEFER;
            }

            (new Logger)->file->info($exception->getMessage(), (array) $exception);
        }

        (new Logger)->syslog->info(sprintf('%s: decision=%s ccert_subject=%s ccert_issuer=%s ccert_fingerprint=%s ccert_pubkey_fingerprint=%s encryption_protocol=%s encryption_cipher=%s encryption_keysize=%s',
            $options['queue_id'] ?? 'NOQUEUE',
            $action, $options['ccert_subject'] ?? '',
            $options['ccert_issuer'] ?? '',
            $options['ccert_fingerprint'] ?? '',
            $options['ccert_pubkey_fingerprint'] ?? '',
            $options['encryption_protocol'] ?? '',
            $options['encryption_cipher'] ?? '',
            $options['encryption_keysize'] ?? ''
        ));

        print (new Responder($action))->respond();

        exit(0);
    }
}
