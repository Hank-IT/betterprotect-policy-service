<?php

namespace App\Support;

use App\RequestHandler;
use App\Exceptions\BetterprotectErrorException;

class Config
{
    const CONFIG = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR  . '..' .  DIRECTORY_SEPARATOR . 'config/app.json';

    /**
     * Read the configuration into an array.
     *
     * @return mixed
     * @throws BetterprotectErrorException
     */
    public function readConfig()
    {
        if (! file_exists(self::CONFIG)) {
            throw new BetterprotectErrorException('Configuration file unavailable', RequestHandler::POSTFIX_ACTION_DEFER);
        }

        $config = json_decode(file_get_contents(self::CONFIG), true);

        if ($config === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new BetterprotectErrorException('Configuration file invalid', RequestHandler::POSTFIX_ACTION_DEFER);
        }

        return $config;
    }
}