<?php

namespace App\Database;

use Illuminate\Database\Capsule\Manager;

interface DatabaseInterface
{
    public function boot(): Manager;
}