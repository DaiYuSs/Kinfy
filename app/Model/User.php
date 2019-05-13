<?php

namespace App\Model;

class User extends BaseModel
{
    protected $table = 'users';
    protected $field2property = [
        'user_name' => 'name'
    ];
    protected $autoCamelCase = false;
    /**
     * User constructor.
     *
     */
//    public function __construct($id)
//    {
//        $user = $this->where('id', $id)->first();
//        foreach ($user as $k => $v) {
//            $this->{$k} = $v;
//        }
//    }
}