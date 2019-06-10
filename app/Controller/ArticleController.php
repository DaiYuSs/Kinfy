<?php

namespace App\Controller;

use Kinfy\DB\DB;
use View as V;
use App\facade\View;

class ArticleController extends BaseController
{
    public function index()
    {
        $db = new DB;
        //获取一行数据的案例
//        $data = $db->table('article')
//            ->select('id', 'title')
//          ->first();
//        var_dump($data);

        //获取全部的数据按照id降序案例
//        $data = $db->table('article')
//            ->orderBy('id','desc')
//            ->get();
//        var_dump($data);

        //表连接查询案例
//        $data = $db->table('article')
//            ->join('users', 'article.user_id=users.id')
//            ->select('article.id as aid', 'article.title', 'users.id as usersid', 'users.username')
//            ->get();
//        print_r($data);

        //insert插入案例
        //        $data = $db->table('article')
//            ->insert(
//                ['title' => '文章33', 'content' => '插入案例测试', 'user_id' => 1]
//            );
//        print_r($data);
//        $data = $db->table('article')
//            ->insert([
//                ['title' => '文章33', 'content' => '插入案例测试', 'user_id' => 1]
//                ...
//            ]);
//        print_r($data);

//        update更新案例
//        $data = $db->table('article')
//            ->where('id','=',1)
//            ->update([
//                'title' => '更新后的文章1'
//            ]);
//        print_r($data);

        //delete删除案例
//        $data = $db->table('article')
//            ->where('id', '=', 8)
//            ->delete();
//        print_r($data);
    }

    /**
     * 模板首页
     */
    public function bladeIndex()
    {
        V::show('index');
        View::test();
        View::show('index');
    }

    /**
     * 新闻主页面
     */
    public function news( )
    {
        View::show('news');
    }

    /**
     * 新闻页面,母版插槽测试页面
     */
    public function news2()
    {
        View::show('news2');
    }

    /**
     * 新闻页面,母版插槽测试页面
     */
    public function news3()
    {
        View::show('news3');
    }

    public function __construct()
    {
        $this->baseNew();
    }

    public function baseNew()
    {
        View::set('title', '金飞科技');
        View::set('subtitle', '首页');
        View::set('header_icon', [
            ['class' => 'icon-location2', 'text' => '浙江温州瓯海茶山街道'],
            ['class' => 'icon-phone2', 'text' => '0577-8888888'],
            ['class' => 'icon-mail', 'text' => 'vip@kinfy.com'],
        ]);
    }
}
