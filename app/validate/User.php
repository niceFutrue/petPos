<?php
declare (strict_types = 1);

namespace app\validate;

use think\Validate;

class User extends Validate{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'name'  =>   ['require', 'max' => 25, 'regex' => '/^[\w|\d]\w+/'],
        'password'  =>  ['require','length'=> '6,16'],
        'phone'  =>  'mobile',
        'email' =>  'email', //^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        'name.require' => '名称必须',
        'name.max'     => '名称最多不能超过25个字符',
        'password.require'   => '密码必须',
        'password.between'   => '密码只能在6-16之间',
        'phone'        => '手机格式错误',
        'email'        => '邮箱格式错误',
    ];
}
