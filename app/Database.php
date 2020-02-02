<?php

namespace App;

use Illuminate\Database\Capsule\Manager as Capsule;

class Database
{
    protected $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function boot()
    {
        $capsule = new Capsule;

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