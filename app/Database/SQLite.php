<?php

namespace App\Database;

use Illuminate\Database\Capsule\Manager;

class SQLite implements DatabaseInterface
{
    public function boot(): Manager
    {
        $capsule = new Manager;

        $capsule->addConnection([
            'driver'    => 'sqlite',
            'database'  => ':memory:',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ]);

        return $capsule;
    }
}