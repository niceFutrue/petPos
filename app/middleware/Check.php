<?php
declare (strict_types = 1);

namespace app\middleware;

use app\admin\util\Tools;
use think\facade\Cookie;
use think\facade\Session;

class Check{
    /**
     * 处理请求
     *
     * @param \think\Request $request
     * @param \Closure       $next
     * @return Response
     */
    public function handle($request, \Closure $next){
        $tools = new Tools();
        $name = request()->header("permissionToken");
        if(empty($name)){
            $name = Cookie::get('petPermissionToken');
        }
        $response = $next($request);
        // 未登录 重定向登录页
        if(empty($name) && $request->action() != "login"){
            return redirect("/admin/loginPage");
        }
        // 已登录 重定向后台页
        if($name && $request->action() == "login"){
            $ctk = $tools -> checkToken($name); // 校验token的时效性
            if($ctk["code"] == 200 ){
                return redirect("/admin/bg");
            }else{
                return redirect("/admin/loginPage");
            }
        }
        return $response;
    }
}
