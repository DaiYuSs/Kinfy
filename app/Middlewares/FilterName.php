<?php

namespace App\Middlewares;

class FilterName
{
    public function handle()
    {
        if (isset($_REQUEST['name']) && $_REQUEST['name']) {
            $_REQUEST['name'] = "#{$_REQUEST['name']}#";
        }
    }
}
