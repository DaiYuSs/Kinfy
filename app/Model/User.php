<?php

namespace App\Model;

class User extends BaseModel
{
//    protected $table = 'users';
    protected $field2property = [
        'user_name' => 'name'
    ];
    protected $autoCamelCase = false;
    public $fieldView = [
        'pass_word' => '***'
    ];

    /**
     * User constructor.
     *
     */
//    public function __construct($id)
//    {
//        parent::__construct();
//        $user = $this->where('id',$id)->first();
//        print_r($user);
//    }

    /**
     * 借书案例
     * @param $book
     */
    public function borrow($book)
    {
        UserBook::add([
            'user_id' => $this->properties[$this->pk],
            'book_id' => $book->properties[$book->pk]
        ]);
    }
}