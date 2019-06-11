<?php

namespace App\Middlewares;

use Kinfy\Config\Config;

class IPWhiteList
{
    public function handle()
    {
        // ip白名单
        $allowips = Config::get('ipwhitelist');
        if ($allowips && !in_array($_SERVER["REMOTE_ADDR"], $allowips)) {
            die('band!!!');
        }
    }
}
