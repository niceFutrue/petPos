<?php
/**
 * Created by PhpStorm.
 * User: 57124
 * Date: 2021/11/24
 * Time: 9:28
 */

namespace app\admin\model;


use think\Model;
/**
 * 管理
 */
class Mag extends Model{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'pet_mag';
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
}