<?php

namespace App;

use Email\Parse;
use App\Support\IPv4;
use App\Logger\Logger;
use Illuminate\Database\Capsule\Manager;

class RequestHandler {
    const POSTFIX_ACTION_DEFER = 'defer_if_permit';
    const POSTFIX_ACTION_DUNNO = 'dunno';

    /**
     * Postfix input data.
     *
     * @var array
     */
    protected $data;

    /**
     * Configuration
     *
     * @var
     */
    protected $config;

    /**
     * @var Logger
     */
    protected $logger;


    /**
     * @var Manager
     */
    protected $capsule;

    /**
     * RequestHandler constructor.
     *
     * @param array   $data
     * @param Manager $database
     * @param Logger  $logger
     */
    public function __construct(array $data, Manager $database, Logger $logger)
    {
        $this->data = $data;

        $this->logger = $logger;

        $this->capsule = $database;
    }

    /**
     * Verify the data and generate a response.
     *
     * @return mixed|string
     */
    public function getResponse()
    {
        $this->handleIPv4Networks();
        $this->handleMailFromDomain();
        $this->handleMailFromLocalPart();

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
            ->orderBy('priority')
            ->first();

        if (empty($queryData)) {
            $this->logger->file->info('Query returned no results');

            return self::POSTFIX_ACTION_DUNNO;
        }

        $action = $this->verify($queryData);

        return $action;
    }

    /**
     * Verify against the client data in conjunction with the sender.
     *
     * @param $queryData
     * @return mixed|string
     */
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

    /**
     * Query all rows with mail_from_domain as sender_type.
     *
     * Set $this->data['sender'] to sender_payload if match is found,
     * so that the later query can identify the data correctly.
     */
    protected function handleMailFromDomain()
    {
        if (empty($this->data['sender']) || $this->data['sender'] === '*') {
            return;
        }

        $mailFromDomains = $this->capsule->getConnection('default')
            ->table('client_sender_access')
            ->select('sender_payload')
            ->where('sender_type', '=', 'mail_from_domain')
            ->get();

        foreach ($mailFromDomains as $mailFromDomain) {
            $result = Parse::getInstance()->parse($this->data['sender']);

            if ($result['email_addresses'][0]['domain'] === $mailFromDomain->sender_payload) {
                $this->data['sender'] = $mailFromDomain->sender_payload;

                $this->logger->file->info('sender matches domain ' . $mailFromDomain->sender_payload);

                break;
            }
        }
    }

    /**
     * Query all rows with mail_from_localpart as sender_type.
     *
     * Set $this->data['sender'] to sender_payload if match is found,
     * so that the later query can identify the data correctly.
     */
    protected function handleMailFromLocalPart()
    {
        if (empty($this->data['sender']) || $this->data['sender'] === '*') {
            return;
        }

        $mailFromLocalParts = $this->capsule->getConnection('default')
            ->table('client_sender_access')
            ->select('sender_payload')
            ->where('sender_type', '=', 'mail_from_localpart')
            ->get();

        foreach ($mailFromLocalParts as $mailFromLocalPart) {
            $result = Parse::getInstance()->parse($this->data['sender']);

            if ($result['email_addresses'][0]['local_part'] === $mailFromLocalPart->sender_payload) {
                $this->data['sender'] = $mailFromLocalPart->sender_payload;

                $this->logger->file->info('sender matches localpart ' . $mailFromLocalPart->sender_payload);

                break;
            }
        }
    }

    /**
     * Query all rows with client_ipv4_net as client_type.
     *
     * Set $this->data['client_address'] to client_payload if match is
     * found, so that the later query can identify the data correctly.
     */
    protected function handleIPv4Networks()
    {
        $ipv4Networks = $this->capsule->getConnection('default')
            ->table('client_sender_access')
            ->select('client_payload')
            ->where('client_type', '=', 'client_ipv4_net')
            ->get();


        foreach ($ipv4Networks as $ipv4Network) {
            if (IPv4::inNetwork($this->data['client_address'], $ipv4Network->client_payload)) {
                $this->data['client_address'] = $ipv4Network->client_payload;

                $this->logger->file->info('client_address is in network ' . $ipv4Network->client_payload);

                break;
            }
        }
    }

    /**
     * Verify the sender.
     *
     * @param $queryData
     * @return mixed|string
     */
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

        $this->logger->file->info('sender_payload ' . $queryData['sender_payload']);

        if ($queryData['sender_payload'] == $this->data['sender']) {
            return $queryData['action'];
        }

        // Default no match return
        $this->logger->file->info('verifySender returns default action');

        return self::POSTFIX_ACTION_DUNNO;
    }
}