<?php

namespace App\Controller;

use App\Model\Article;
use App\Model\User;
use Kinfy\DB\DB;

class UserController extends BaseController
{
    public function index($id, $name = null)
    {
        echo $id . ' ' . $name;
    }

    public function add($name)
    {
        $user = new User();
        $user->name = '3378';
        $user->save();
//
//        $r = User::insert([
//            'user_name' => $name
//        ]);
//        print_r($r);

//        $r = Article::getHot();
//        print_r($r);
    }

    public function del($id = '空空如也')
    {
        echo 'user del:' . $id;
    }

    public function beforedel($id = null)
    {
        if ($id == null) {
            echo '请输入需要删除人的id';
            die();
        }
    }

    public function afterdel($id)
    {
        if ($id == '1') {
            echo '<br>这是超级管理员';
        }
    }

    public function put()
    {
        echo 'user put';
    }

    public function content($id, $content)
    {
        echo '用户:' . $id . '内容:' . $content;
    }

    public function article($user_id)
    {
        $user = new User($user_id);
        $r = $user->getArticle();
    }

    public function detail($user_id)
    {
        //echo '查看用户:' . $user_id . '信息';
        $user = new User();
        $user->where('id', $user_id)->first();

        echo $user->name;
    }

    public function userList()
    {
//        echo '查看用户列表';
        $user = new User();
        $isadmin = false;
        if (!$isadmin) {
            $user->fieldView['mobile'] = '****';
        }
        $data = $user->get();
        print_r($data);
    }
}
