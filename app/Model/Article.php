<?php

namespace App\Model;

use Kinfy\DB\DB;

class Article extends BaseModel
{
    public static function getHot($num = 10)
    {
        return self::where('is_hot', 1)
            ->take($num)
            ->orderBy('id', 'desc')
            ->get();
    }
}
