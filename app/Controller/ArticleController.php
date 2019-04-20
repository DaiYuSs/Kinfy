<?php

namespace App\Controller;

use Kinfy\DB\DB;

class ArticleController extends BaseController
{
    public function index()
    {
        $db = new DB;
        $data = $db->table('article')
//            ->where('cate_id', '=', '10')
//            ->where('user_id', '=', '6')
//            ->getAll();
            ->first();
        var_dump($data);
    }
}
