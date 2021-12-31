<?php
/**
 * Created by PhpStorm.
 * User: 57124
 * Date: 2021/11/22
 * Time: 14:37
 */

namespace app\admin\model;


use think\Model;

class User extends Model{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'pet_user';
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'name'        => 'string',
        'phone'       => 'string',
        'email'       => 'string',
        'password'    => 'string',
        'status'      => 'int',
        'updated'     => 'datetime',
        'created'     => 'datetime',
    ];
//    public function getPassword($name){
//        return User::where('name',$name)->where("status",1)->value("password");
//    }
}