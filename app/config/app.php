<?php

return [
    'status' => 'RELEASE',//网站运行状态，RELEASE,DEBUG,SHUTDOWN
    'providers' => [
        'View' => \Kinfy\View\View::class
    ],
    'provider_interface' => [
        'View' => \Kinfy\View\IView::class,
    ]
];
