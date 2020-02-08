<?php

namespace Tests\Unit;

use App\Database\SQLite;

trait Database
{
    /**
     * Create in memory database and table.
     *
     * @return \Illuminate\Database\Capsule\Manager
     */
    protected function getDatabase()
    {
        $database = (new SQLite)->boot();

        $database->getConnection('default')->statement('CREATE TABLE `client_sender_access` (`id` INTEGER PRIMARY KEY AUTOINCREMENT, `client_type` varchar(191) NOT NULL, `client_payload` varchar(1024) DEFAULT NULL, `sender_type` varchar(191) NOT NULL, `sender_payload` varchar(1024) DEFAULT NULL, `action` varchar(191) NOT NULL, `priority` integer default 0)');

        $database->getConnection('default')->table('client_sender_access')->insert($this->databaseData);

        return $database;
    }
}