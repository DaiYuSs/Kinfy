<?php

namespace App\Controller;

use App\Model\Book;

class BookController extends BaseController
{
    public function add()
    {
        $book = new Book();
        $book->name = '基督山伯爵';
        $book->author = '尼古拉斯赵四';
        $book->save();
    }
}
