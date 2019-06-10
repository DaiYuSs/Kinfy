<?php

namespace App\facade;

class View extends \Kinfy\Facade\Facade
{
    protected static $provider = \Kinfy\View\View::class;

    public static function test()
    {
        echo 'test';
    }
}
