<?php

namespace App\Model;

class Book extends BaseModel
{
//    protected $table = 'books';
    protected $pk = 'uuid';
    protected $autoPk = false;

    protected function genPk()
    {
        return floor(mt_rand(10000000, 99999999));
    }
}
