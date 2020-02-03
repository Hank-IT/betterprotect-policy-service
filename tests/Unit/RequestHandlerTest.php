<?php

namespace Tests\Unit;

use App\Logger\Logger;
use App\RequestHandler;
use App\Database\SQLite;
use PHPUnit\Framework\TestCase;

class RequestHandlerTest extends TestCase
{
    protected $databaseData = [
        ['client_type' => 'client_hostname',         'client_payload' => 'mx00.contoso.com',                       'sender_type' => '*', 'sender_payload' => '*', 'action' => 'OK'],
        ['client_type' => 'client_reverse_hostname', 'client_payload' => 'reverse2.contoso.com',                   'sender_type' => '*', 'sender_payload' => '*', 'action' => 'OK'],
        ['client_type' => 'client_ipv4',             'client_payload' => '192.168.100.3',                          'sender_type' => '*', 'sender_payload' => '*', 'action' => 'OK'],
        ['client_type' => 'client_ipv6',             'client_payload' => '2001:0db8:85a3::8a2e:0370:7334',         'sender_type' => '*', 'sender_payload' => '*', 'action' => 'OK'],
        ['client_type' => 'client_ipv4_net',         'client_payload' => '192.168.178.0/24',                       'sender_type' => '*', 'sender_payload' => '*', 'action' => 'OK'],

        ['client_type' => '*', 'client_payload' => '*', 'sender_type' => 'mail_from_address',   'sender_payload' => 'mail1@contoso.com', 'action' => 'OK'],
        ['client_type' => '*', 'client_payload' => '*', 'sender_type' => 'mail_from_domain',    'sender_payload' => '1234contoso.com', 'action' => 'OK'],
        ['client_type' => '*', 'client_payload' => '*', 'sender_type' => 'mail_from_localpart', 'sender_payload' => 'mail2', 'action' => 'OK'],
    ];

    public function testClientTypesSenderWildcardOK()
    {
        $database = $this->getDatabase();
        $database->getConnection('default')->table('client_sender_access')->insert($this->databaseData);

        ['client_type' => '', 'client_payload' => '', 'sender_type' => '', 'sender_payload' => '', 'action' => ''];

        $postfixData = [
            ['client_name' => 'mx00.contoso.com', 'reverse_client_name' => 'reverse1.contoso.com', 'client_address' => '192.168.100.1',                  'sender' => 'mail1@contoso.com'],
            ['client_name' => 'mx01.contoso.com', 'reverse_client_name' => 'reverse2.contoso.com', 'client_address' => '192.168.100.2',                  'sender' => 'mail@2contoso.com'],
            ['client_name' => 'mx02.contoso.com', 'reverse_client_name' => 'reverse3.contoso.com', 'client_address' => '192.168.100.3',                  'sender' => 'mail@2contoso.com'],
            ['client_name' => 'mx03.contoso.com', 'reverse_client_name' => 'reverse4.contoso.com', 'client_address' => '2001:0db8:85a3::8a2e:0370:7334', 'sender' => 'mail@3contoso.com'],
            ['client_name' => 'mx04.contoso.com', 'reverse_client_name' => 'reverse5.contoso.com', 'client_address' => '192.168.178.1',                  'sender' => 'mail@3contoso.com'],
        ];

        foreach($postfixData as $option) {
            $action = (new RequestHandler($option, $database, new Logger))->getResponse();

            $this->assertEquals('OK', $action);
        }
    }

    public function testSenderTypesClientWildcardOK()
    {
        $database = $this->getDatabase();
        $database->getConnection('default')->table('client_sender_access')->insert($this->databaseData);

        ['client_type' => '', 'client_payload' => '', 'sender_type' => '', 'sender_payload' => '', 'action' => ''];

        $postfixData = [
            ['client_name' => 'mx00.contoso.com', 'reverse_client_name' => 'reverse1.contoso.com', 'client_address' => '192.168.100.4', 'sender' => 'mail1@contoso.com'],
            ['client_name' => 'mx01.contoso.com', 'reverse_client_name' => 'reverse2.contoso.com', 'client_address' => '192.168.100.5', 'sender' => 'mail1@1234contoso.com'],
            ['client_name' => 'mx02.contoso.com', 'reverse_client_name' => 'reverse3.contoso.com', 'client_address' => '192.168.100.6', 'sender' => 'mail2@contoso.com'],
        ];

        foreach($postfixData as $option) {
            $action = (new RequestHandler($option, $database, new Logger))->getResponse();

            $this->assertEquals('OK', $action);
        }
    }

    /**
     * Create in memory database and table.
     *
     * @return \Illuminate\Database\Capsule\Manager
     */
    protected function getDatabase()
    {
        $database = (new SQLite)->boot();

        $database->getConnection('default')->statement('CREATE TABLE `client_sender_access` (`id` INTEGER PRIMARY KEY AUTOINCREMENT, `client_type` varchar(191) NOT NULL, `client_payload` varchar(1024) DEFAULT NULL, `sender_type` varchar(191) NOT NULL, `sender_payload` varchar(1024) DEFAULT NULL, `action` varchar(191) NOT NULL)');

        return $database;
    }
}