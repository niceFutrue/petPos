<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\admin\util\Tools;
use app\BaseController;
use app\validate\User;
use Qcloud\Sms\SmsSingleSender;
use think\Exception;
use think\exception\ValidateException;
use think\facade\App;
use think\facade\Cookie;
use think\facade\Db;
use think\facade\Session;
use think\facade\View;

/* 管理者 */
class Index extends BaseController {
    public function test(){
        $tools = new Tools();
        echo $tools ->encrypt("123123")."<br>";
        echo $tools ->decrypt("73LxndXVHNA=")."<br>";
        echo App::getRootPath()." <br> ";
        //echo date('Y-m-d H:i:s')."<br>";
        echo strtotime(date('Y-m-d H:i:s'))."<br>";
        return '您好！这是一个test示例';
    }

    protected $middleware = ['app\middleware\Check'=>['except'=> ['codeSms','loginApi','register','registerApi']],];

    // 页面_注册
    public function register(){
        return View::fetch();
    }
    // 页面_登录
    public function login(){
        return View::fetch();
    }
    // 后台页
    public function admin(){
        $name = "小薇";
        View::assign(["userName" =>$name]);
        return View::fetch();
    }

    // 注销
    public function logout(){
        Cookie::delete('petPermissionToken'); //删除cookie
        return redirect("/admin/loginPage");
    }

}
