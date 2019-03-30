<?php

require_once __DIR__ . './../vendor/autoload.php';
require_once __DIR__ . './../app/router/web.php';

use Kinfy\Http\Router;
Router::$namespace = '\\App\\Controller\\';

Router::$notFound = function () {
    require_once __DIR__ . './../app/resource/404.html';
};

Router::dispatch();