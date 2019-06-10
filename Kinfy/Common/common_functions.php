<?php

if (!function_exists('config')) {
    function config($key)
    {
        return \Kinfy\Config\Config::get($key);
    }
}
