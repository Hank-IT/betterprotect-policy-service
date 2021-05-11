<?php

namespace App\Database;

use Illuminate\Database\Capsule\Manager;

class MySQL implements DatabaseInterface
{
    protected $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function boot(): Manager
    {
        $capsule = new Manager;

        $capsule->addConnection([
            'driver'    => 'mysql',
            'host'      => $this->config['hostname'],
            'database'  => $this->config['database'],
            'username'  => $this->config['username'],
            'password'  => $this->config['password'],
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ]);

        return $capsule;
    }
}