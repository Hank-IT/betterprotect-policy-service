<?php

namespace App\Support;

class IPv4
{
    public static function inNetwork($ipv4, $ipv4Network)
    {
        if (strpos($ipv4Network, '/') == false) {
            $ipv4Network .= '/32';
        }

        if (! static::isValidIPv4Net($ipv4Network)) {
            return false;
        }

        list($ipv4Network, $netmask) = explode('/', $ipv4Network, 2);

        $range_decimal = ip2long($ipv4Network);

        $ip_decimal = ip2long($ipv4);

        $wildcard_decimal = pow(2, (32 - $netmask )) - 1;

        $netmask_decimal = ~ $wildcard_decimal;

        return (($ip_decimal & $netmask_decimal) == ($range_decimal & $netmask_decimal));
    }

    public static function isValidIPv4Net($ipv4Network)
    {
        return preg_match('#^(?:((?:0)|(?:2(?:(?:[0-4][0-9])|(?:5[0-5])))|(?:1?[0-9]{1,2}))\.((?:0)|(?:2(?:(?:[0-4][0-9])|(?:5[0-5])))|(?:1?[0-9]{1,2}))\.((?:0)|(?:2(?:(?:[0-4][0-9])|(?:5[0-5])))|(?:1?[0-9]{1,2}))\.((?:0)|(?:2(?:(?:[0-4][0-9])|(?:5[0-5])))|(?:1?[0-9]{1,2}))(?:/((?:(?:0)|(?:3[0-2])|(?:[1-2]?[0-9]))))?)$#', $ipv4Network);
    }
}