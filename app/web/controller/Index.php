<?php
declare (strict_types = 1);

namespace app\web\controller;

class Index{
    public function index(){
        return '您好！这是一个[web]示例应用';
    }

    public function hello(){
        return json(["code"=>200,"msg"=>"请求成功！","data"=>'您好！hello']);
    }

}
