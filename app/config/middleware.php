<?php
return [
    'filter_name' => App\Middlewares\FilterName::class,
    'filter_ab' => [
        App\Middlewares\B::class,
        App\Middlewares\A::class
    ],
    'ipband' => App\Middlewares\IPWhiteList::class,
];
