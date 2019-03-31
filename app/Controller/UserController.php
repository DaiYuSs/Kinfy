<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/26
 * Time: 14:20
 */

namespace App\Controller;


class UserController
{

    public function index($id, $name = null)
    {
        echo $id.$name;
    }

    public function add($id)
    {
        echo 'user add:' . $id;
    }

    public function del($id = '空空如也')
    {
        echo 'user del:' . $id;
    }

    public function put()
    {
        echo 'user put';
    }

    public function content($id,$content){
        echo '用户:'.$id.'内容:'.$content;
    }
}