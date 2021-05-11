<?php

namespace Tests\Unit;

use App\Logger\Logger;
use App\RequestHandler;
use PHPUnit\Framework\TestCase;

class ClientRejectTest extends TestCase
{
    use Database;

    protected $databaseData = [
        ['client_type' => 'client_hostname',             'client_payload' => 'mx03.contoso.com',               'sender_type' => 'mail_from_address', 'sender_payload' => 'mail3@contoso.com', 'action' => 'reject', 'priority' => 1],
        ['client_type' => 'client_reverse_hostname',     'client_payload' => 'mx04.reverse.contoso.com',       'sender_type' => 'mail_from_address', 'sender_payload' => 'mail4@contoso.com', 'action' => 'reject', 'priority' => 2],
        ['client_type' => 'client_ipv4',                 'client_payload' => '192.168.100.5',                  'sender_type' => 'mail_from_address', 'sender_payload' => 'mail5@contoso.com', 'action' => 'reject', 'priority' => 3],
        ['client_type' => 'client_ipv6',                 'client_payload' => '2001:0db8:85a3::8a2e:0370:7334', 'sender_type' => 'mail_from_address', 'sender_payload' => 'mail6@contoso.com', 'action' => 'reject', 'priority' => 4],
        ['client_type' => 'client_ipv4_net',             'client_payload' => '192.168.178.0/24',               'sender_type' => 'mail_from_address', 'sender_payload' => 'mail7@contoso.com', 'action' => 'reject', 'priority' => 5],

        ['client_type' => 'client_hostname',             'client_payload' => 'mx05.contoso.com',               'sender_type' => 'mail_from_address', 'sender_payload' => 'mail7@contoso.com', 'action' => 'ok',     'priority' => 6],
        ['client_type' => 'client_reverse_hostname',     'client_payload' => 'reverse.mx06.contoso.com',       'sender_type' => 'mail_from_address', 'sender_payload' => 'mail7@contoso.com', 'action' => 'ok',     'priority' => 7],
        ['client_type' => 'client_ipv4',                 'client_payload' => '192.168.100.10',                 'sender_type' => 'mail_from_address', 'sender_payload' => 'mail7@contoso.com', 'action' => 'ok',     'priority' => 8],
        ['client_type' => 'client_ipv6',                 'client_payload' => '2001:0db8:85a3::8a2e:0370:7234', 'sender_type' => 'mail_from_address', 'sender_payload' => 'mail7@contoso.com', 'action' => 'ok',     'priority' => 9],
        ['client_type' => 'client_ipv4_net',             'client_payload' => '192.168.177.0/24',               'sender_type' => 'mail_from_address', 'sender_payload' => 'mail7@contoso.com', 'action' => 'ok',     'priority' => 10],


    ];

    /**
     * Client Reject Tests
     */

    public function testClientHostnameReject()
    {
        $database = $this->getDatabase();

        $postfixData = ['client_name' => 'mx03.contoso.com', 'reverse_client_name' => 'reverse.mx03.contoso.com', 'client_address' => '192.168.100.7', 'sender' => 'mail3@contoso.com'];

        $action = (new RequestHandler($postfixData, $database, new Logger))->getResponse();

        $this->assertEquals('reject', $action);
    }

    public function testClientReverseHostnameReject()
    {
        $database = $this->getDatabase();

        $postfixData = ['client_name' => 'mx04.contoso.com', 'reverse_client_name' => 'mx04.reverse.contoso.com', 'client_address' => '192.168.100.8', 'sender' => 'mail4@contoso.com'];

        $action = (new RequestHandler($postfixData, $database, new Logger))->getResponse();

        $this->assertEquals('reject', $action);
    }

    public function testClientIPv4Reject()
    {
        $database = $this->getDatabase();

        $postfixData = ['client_name' => 'mx04.contoso.com', 'reverse_client_name' => 'mx04.reverse.contoso.com', 'client_address' => '192.168.100.5', 'sender' => 'mail5@contoso.com'];

        $action = (new RequestHandler($postfixData, $database, new Logger))->getResponse();

        $this->assertEquals('reject', $action);
    }

    public function testClientIPv6Reject()
    {
        $database = $this->getDatabase();

        $postfixData = ['client_name' => 'mx04.contoso.com', 'reverse_client_name' => 'mx04.reverse.contoso.com', 'client_address' => '2001:0db8:85a3::8a2e:0370:7334', 'sender' => 'mail6@contoso.com'];

        $action = (new RequestHandler($postfixData, $database, new Logger))->getResponse();

        $this->assertEquals('reject', $action);
    }

    public function testClientIPv4NetReject()
    {
        $database = $this->getDatabase();

        $postfixData = [];
        for ($i=0;$i<=255;$i++) {
            $postfixData[] = ['client_name' => 'mx04.contoso.com', 'reverse_client_name' => 'mx04.reverse.contoso.com', 'client_address' => '192.168.178.' . $i, 'sender' => 'mail7@contoso.com'];
        }

        foreach($postfixData as $option) {
            $action = (new RequestHandler($option, $database, new Logger))->getResponse();

            $this->assertEquals('reject', $action);
        }
    }

    /**
     * Client OK Tests
     */

    public function testClientHostnameOK()
    {
        $database = $this->getDatabase();

        $postfixData = ['client_name' => 'mx05.contoso.com', 'reverse_client_name' => 'reverse.mx03.contoso.com', 'client_address' => '192.168.100.7', 'sender' => 'mail7@contoso.com'];

        $action = (new RequestHandler($postfixData, $database, new Logger))->getResponse();

        $this->assertEquals('ok', $action);
    }

    public function testClientReverseHostnameOK()
    {
        $database = $this->getDatabase();

        $postfixData = ['client_name' => 'mx05.contoso.com', 'reverse_client_name' => 'reverse.mx06.contoso.com', 'client_address' => '192.168.100.7', 'sender' => 'mail7@contoso.com'];

        $action = (new RequestHandler($postfixData, $database, new Logger))->getResponse();

        $this->assertEquals('ok', $action);
    }

    public function testClientIPv4OK()
    {
        $database = $this->getDatabase();

        $postfixData = ['client_name' => 'mx05.contoso.com', 'reverse_client_name' => 'reverse.mx06.contoso.com', 'client_address' => '192.168.100.10', 'sender' => 'mail7@contoso.com'];

        $action = (new RequestHandler($postfixData, $database, new Logger))->getResponse();

        $this->assertEquals('ok', $action);
    }

    public function testClientIPv6OK()
    {
        $database = $this->getDatabase();

        $postfixData = ['client_name' => 'mx05.contoso.com', 'reverse_client_name' => 'reverse.mx06.contoso.com', 'client_address' => '2001:0db8:85a3::8a2e:0370:7234', 'sender' => 'mail7@contoso.com'];

        $action = (new RequestHandler($postfixData, $database, new Logger))->getResponse();

        $this->assertEquals('ok', $action);
    }

    public function testClientIPv4NetOK()
    {
        $database = $this->getDatabase();

        $postfixData = [];
        for ($i=0;$i<=255;$i++) {
            $postfixData[] = ['client_name' => 'mx04.contoso.com', 'reverse_client_name' => 'mx04.reverse.contoso.com', 'client_address' => '192.168.177.' . $i, 'sender' => 'mail7@contoso.com'];
        }

        foreach($postfixData as $option) {
            $action = (new RequestHandler($option, $database, new Logger))->getResponse();

            $this->assertEquals('ok', $action);
        }
    }

    /**
     * Client Dunno Tests
     */

    public function testClientHostnameDunno()
    {
        $database = $this->getDatabase();

        $postfixData = ['client_name' => 'mx03.contoso.com', 'reverse_client_name' => 'mx04.reverse.contoso.com', 'client_address' => '192.168.100.7', 'sender' => 'no-match@contoso.com'];

        $action = (new RequestHandler($postfixData, $database, new Logger))->getResponse();

        $this->assertEquals('dunno', $action);
    }

    public function testClientReverseHostnameDunno()
    {
        $database = $this->getDatabase();

        $postfixData = ['client_name' => 'mx04.contoso.com', 'reverse_client_name' => 'mx04.reverse.contoso.com', 'client_address' => '192.168.100.8', 'sender' => 'no-match@contoso.com'];

        $action = (new RequestHandler($postfixData, $database, new Logger))->getResponse();

        $this->assertEquals('dunno', $action);
    }

    public function testClientIPv4Dunno()
    {
        $database = $this->getDatabase();

        $postfixData = ['client_name' => 'mx04.contoso.com', 'reverse_client_name' => 'mx04.reverse.contoso.com', 'client_address' => '192.168.100.5', 'sender' => 'no-match@contoso.com'];

        $action = (new RequestHandler($postfixData, $database, new Logger))->getResponse();

        $this->assertEquals('dunno', $action);
    }

    public function testClientIPv6Dunno()
    {
        $database = $this->getDatabase();

        $postfixData = ['client_name' => 'mx04.contoso.com', 'reverse_client_name' => 'mx04.reverse.contoso.com', 'client_address' => '2001:0db8:85a3::8a2e:0370:7334', 'sender' => 'no-match@contoso.com'];

        $action = (new RequestHandler($postfixData, $database, new Logger))->getResponse();

        $this->assertEquals('dunno', $action);
    }

    public function testClientIPv4NetDunno()
    {
        $database = $this->getDatabase();

        $postfixData = [];
        for ($i=0;$i<=255;$i++) {
            $postfixData[] = ['client_name' => 'mx04.contoso.com', 'reverse_client_name' => 'mx04.reverse.contoso.com', 'client_address' => '192.168.178.' . $i, 'sender' => 'no-match@contoso.com'];
        }

        foreach($postfixData as $option) {
            $action = (new RequestHandler($option, $database, new Logger))->getResponse();

            $this->assertEquals('dunno', $action);
        }
    }
}