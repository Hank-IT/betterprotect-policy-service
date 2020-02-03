<?php

namespace App;

use App\Logger\Logger;
use App\Exceptions\BetterprotectErrorException;

class RequestHandler {
    const CONFIG = __DIR__ . DIRECTORY_SEPARATOR . '../config/app.json';

    const POSTFIX_ACTION_DEFER = 'defer_if_permit';
    const POSTFIX_ACTION_DUNNO = 'dunno';

    protected $data;

    protected $config;

    protected $logger;

    protected $capsule;

    public function __construct(array $data)
    {
        $this->data = $data;

        $this->logger = new Logger;

        $config = $this->readConfig();

        $this->capsule = (new Database($config['database']))->boot();
    }

    public function getResponse()
    {
        // ToDo
        // Verify if $this->data['client_address'] is inside a configured client_ipv4_net
        // Query all ipv4 networks
        // If match is found set $this->data['client_address'] = $queryData['client_payload']

        $queryData = (array) $this->capsule->getConnection('default')
            ->table('client_sender_access')
            ->select(['client_type', 'client_payload', 'sender_type', 'sender_payload', 'action'])
            ->where(function($query) {
                $query->where('client_payload', '=', $this->data['client_name'])
                    ->orWhere('client_payload', '=', $this->data['reverse_client_name'])
                    ->orWhere('client_payload', '=', $this->data['client_address'])
                    ->orWhere('client_payload', '=', '*');
            })
            ->where(function($query) {
                $query->where('sender_payload', '=', $this->data['sender'])
                    ->orWhere('sender_payload', '=', '*');
            })
            ->first();

        if (empty($queryData)) {
            $this->logger->file->info('Query returned no results');

            return self::POSTFIX_ACTION_DUNNO;
        }

        $action = $this->verify($queryData);

        return $action;
    }

    protected function verify($queryData)
    {
        // Wildcard client, go straight to sender verify
        if ($queryData['client_payload'] == '*') {
            $this->logger->file->info('client_payload is wildcard');

            return $this->verifySender($queryData);
        }

        switch($queryData['client_type']) {
            case 'client_hostname':
                if ($queryData['client_payload'] == $this->data['client_name']) {
                    return $this->verifySender($queryData);
                }
                break;
            case 'client_reverse_hostname':
                if ($queryData['client_payload'] == $this->data['reverse_client_name']) {
                    return $this->verifySender($queryData);
                }
                break;
            case 'client_ipv4':
            case 'client_ipv6':
            case 'client_ipv4_net':
                if ($queryData['client_payload'] == $this->data['client_address']) {
                    return $this->verifySender($queryData);
                }
                break;
        };

        // Default no match return
        return self::POSTFIX_ACTION_DUNNO;
    }

    protected function verifySender($queryData)
    {
        // Allow wildcard senders
        if ($queryData['sender_payload'] == '*') {
            $this->logger->file->info('sender_payload is wildcard');

            return $queryData['action'];
        }

        // Allow empty sender
        if (empty($this->data['sender'])) {
            $this->logger->file->info('Sender is empty');

            return $queryData['action'];
        }

        // Check for specific rules
        switch($queryData['sender_type']) {
            case 'mail_from_address':
                $this->logger->file->info('sender_payload ' . $queryData['sender_payload']);
                $this->logger->file->info('postfix ' . $this->data['sender']);

                if ($queryData['sender_payload'] == $this->data['sender']) {
                    return $queryData['action'];
                }
                break;
            case 'mail_from_domain':
                // ToDo
                // Parse E-Mail to get domain
                break;
            case 'mail_from_localpart':
                // ToDo
                // Parse E-mail to get localpart
                break;
        }

        // Default no match return
        $this->logger->file->info('verifySender returns default action');

        return self::POSTFIX_ACTION_DUNNO;
    }

    protected function readConfig()
    {
        if (! file_exists(self::CONFIG)) {
            throw new BetterprotectErrorException('Configuration file unavailable', self::POSTFIX_ACTION_DEFER);
        }

        $config = json_decode(file_get_contents(self::CONFIG), true);

        if ($config === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new BetterprotectErrorException('Configuration file invalid', self::POSTFIX_ACTION_DEFER);
        }

        return $config;
    }
}