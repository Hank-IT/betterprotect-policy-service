<?php

namespace App;

use PDO;
use PDOException;
use App\Exceptions\BetterprotectErrorException;

class RequestHandler {
    const CONFIG = '../config/app.json';

    const POSTFIX_ACTION_DEFER = 'defer';
    const POSTFIX_ACTION_DUNNO = 'dunno';

    protected $data;

    protected $config;

    protected $logger;

    public function __construct(array $data)
    {
        $this->data = $data;

        $this->logger = new Logger;
    }

    public function getResponse()
    {
        $queryData = $this->queryDatabase($this->readConfig()['database']);

        // There should always be a match, except when
        // there isn't any rule configured at all.
        if (empty($queryData)) {
            $this->logger->log('Response ' . self::POSTFIX_ACTION_DUNNO);

            $this->logger->debug('Query returned no results');

            return self::POSTFIX_ACTION_DUNNO;
        }

        $action = $this->verify($queryData);

        $this->logger->log('Response ' . $action);

        return $action;
    }

    protected function verify($queryData)
    {
        // Wildcard client, go straight to sender verify
        if ($queryData['client_payload'] == '*') {
            $this->logger->debug('Client payload is wildcard');

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
            $this->logger->debug('Sender payload is wildcard');

            return $queryData['action'];
        }

        // Allow empty sender
        if (empty($this->data['sender'])) {
            $this->logger->debug('Sender is empty');

            return $queryData['action'];
        }

        // Check for specific rules
        switch($queryData['sender_type']) {
            case 'mail_from_address':
                $this->logger->debug('sender_payload ' . $queryData['sender_payload']);
                $this->logger->debug('postfix ' . $this->data['sender']);

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
        $this->logger->debug('verifySender returns default action');

        return self::POSTFIX_ACTION_DUNNO;
    }

    protected function queryDatabase(array $config) {
        try {
            $connection = new PDO("mysql:host=" . $config['hostname'] . ";dbname=" . $config['database'], $config['username'], $config['password']);

            $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new BetterprotectErrorException('Database connection failed', self::POSTFIX_ACTION_DEFER);
        }

        // ToDo
        // Verify if $this->data['client_address'] is inside a configured client_ipv4_net
        // Query all ipv4 networks
        // If match is found set $this->data['client_address'] = $queryData['client_payload']

        $stmt = $connection->prepare("SELECT client_type, client_payload, sender_type, sender_payload, action 
                                                FROM client_sender_access 
                                                WHERE (client_payload = :client_name 
                                                or client_payload = :reverse_client_name 
                                                or client_payload = :client_address 
                                                or client_payload = '*') 
                                                AND 
                                                (sender_payload = :sender
                                                or sender_payload = '*') 
                                                LIMIT 1");

        $stmt->execute([
            'client_name' => $this->data['client_name'],
            'reverse_client_name' => $this->data['reverse_client_name'],
            'client_address' => $this->data['client_address'],
            'sender' => $this->data['sender'],
        ]);

        $row = $stmt->fetch();

        $connection = null;

        return $row;
    }

    protected function readConfig()
    {
        if (! file_exists(__DIR__ . DIRECTORY_SEPARATOR . self::CONFIG)) {
            throw new BetterprotectErrorException('Configuration file unavailable', self::POSTFIX_ACTION_DEFER);
        }

        $config = json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . self::CONFIG), true);

        if ($config === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new BetterprotectErrorException('Configuration file invalid', self::POSTFIX_ACTION_DEFER);
        }

        return $config;
    }
}