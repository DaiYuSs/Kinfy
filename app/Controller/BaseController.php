<?php
/**
 * Created by PhpStorm.
 * User: l
 * Date: 2019/4/13
 * Time: 9:47
 */

namespace App\Controller;

class BaseController
{
    public function before()
    {
        echo '全员拦截...<br>';
    }
}