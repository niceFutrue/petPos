<?php
/**
 * Created by PhpStorm.
 * User: 57124
 * Date: 2021/11/25
 * Time: 14:27
 */

namespace app\admin\controller;


use app\admin\util\Tools;
use app\BaseController;
use app\validate\User;
use PHPMailer\PHPMailer\PHPMailer;
use think\Exception;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Cookie;
use think\facade\Db;
use think\facade\Session;
use think\facade\View;

class VerifyBefore extends BaseController{


    // 清理缓存
    function clearRuntime(){
        //数据库获取数据
        $tools = new Tools(); // 工具类
        //$param = Session::get("petPermissionToken");
        $param = Cookie::get('petPermissionToken');
        $tkObj = $tools -> checkToken($param);// 创建 token
        $user = Db::name("pet_mag")->where("name",$tkObj["username"])->value("id");
        if(empty($user)){
            return json(["code"=>401,"msg"=>"请登录！"]);
        }else{
            Cache::clear(); // 清空缓存
            return json(["code"=>200,"msg"=>"删除成功！"]);
        }
    }

    /**
     * 注册请求
     * 用户名已注册 4000，手机号已注册 4001，邮箱已注册 4002，
     */
    public function registerApi(){
        $name = input("username");
        $pswd = input("password");
        $me = input("mobileEmail");
        $tools = new Tools();
        $mag = new \app\admin\model\Mag();
        //$isEmail = preg_match('/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/', $me, $matches);
        $isMobile = preg_match('/^[1][3,4,5,7,8,9][0-9]{9}$/', $me, $matches);
        $isName = $mag::where("name",$name)->value("id");
        if($isName){
            return json(["msg"=>"该用户名已注册","code"=>4001]);
        }
        // 手机
        if ($isMobile == 1) {
            $data = [
                'name'  => $name,
                "password" => $tools ->encrypt($pswd),
                "phone" => $me,
                "updated" => time(),
                "created" => time(),
            ];
            $isPhone = $mag::where("phone",$me)->value("id");
            if($isPhone){
                return json(["msg"=>"该手机号已注册","code"=>4002]);
            }
        }else{
            $data = [
                'name'  => $name,
                "password" => $tools ->encrypt($pswd),
                'email' => $me,
                "updated" => time(),
                "created" => time(),
            ];
            $isEmail = $mag::where("email",$me)->value("id");
            if($isEmail){
                return json(["msg"=>"该邮箱已注册","code"=>4003]);
            }
        }
        // 注册验证
        try {
            validate(User::class)->check($data); // 属性验证
            $tools = new Tools(); // 工具类
            $data["status"] = 1;
            $data["password"] = md5($pswd); // 将密码加密存放
            Db::name("pet_mag")->insert($data);
            $tk = $tools -> createToken($name);// 创建 token
            //Session::set("petPermissionToken",$tk); //session
            Cookie::set('petPermissionToken', $tk, TOKEN_TIME_HALF_YEAR); //三个月

            // 记录日志
            $logData = "msg=注册，name=".$name."，date=".date("Y-m-d H:i:s")."；\n";
            $logPath = $tools ->createLogFile();
            $logFile = fopen($logPath,"a");
            fwrite($logFile,$logData);
            fclose($logFile);

            return json(["msg"=>"注册成功！", "code"=>200, "url"=>"/admin/bg","token"=>$tk]);
        } catch (ValidateException $e) {
            return json(["msg"=>$e->getError(), "code"=>5001]);
            //dump($e->getError()); // 验证失败 输出错误信息
        }
    }
    // 校验_账号名（注册）
    public function checkName(){
        $name = input("username");//该参数可用户名
        if($name){
            $user = new \app\admin\model\User();
            $val = $user::where("name",$name)->value("name");
            if(empty($val)){
                return json(["error"=>0,"msg"=>"校验成功！"]);
            }
            return json(["error"=>1,"msg"=>"对不起，该账号已存在！"]);
        }
        return json(["error"=>1,"msg"=>"账号不可为空！"]);
    }
    // 校验_手机号/邮箱（注册）
    public function checkPhoneEmail(){
        $me = input("mobileEmail");//手机号、邮箱
        $isEmail = preg_match('/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/', $me, $matches);
        $isMobile = preg_match('/^[1][3,4,5,7,8,9][0-9]{9}$/', $me, $matches);
        //return json(["error"=>1,"msg"=>"校验失败，该账号已存在！","isP"=>$isMobile,"isE"=>$isEmail]);
        if(!empty($me)){
            $user = new \app\admin\model\User();
            $pageCode = mt_rand(100000,999999);
            if($isMobile == 1 && $isEmail == 0){
                $val = $user::where("phone",$me)->value("name");
                if(empty($val)){
                    return json(["error"=>0,"msg"=>"校验成功！","pageCode"=>$pageCode,"isParam"=>"mobile"]);
                }
                return json(["error"=>1,"msg"=>"校验失败，该手机号已存在！"]);
            }
            if($isMobile == 0 && $isEmail == 1){
                $val = $user::where("email",$me)->value("name");
                if(empty($val)){
                    return json(["error"=>0,"msg"=>"校验成功！","pageCode"=>$pageCode,"isParam"=>"email"]);
                }
                return json(["error"=>1,"msg"=>"校验失败，该邮箱已存在！"]);
            }
        }
        return json(["error"=>1,"msg"=>"账号不可为空！"]);
    }
    // 验证码_短信/邮箱（注册）
    public function codeRegister(){
        try{
            $me = input('mobileEmail');
            $isEmail = preg_match('/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/', $me, $matches);
            $isMobile = preg_match('/^[1][3,4,5,7,8,9][0-9]{9}$/', $me, $matches);
            if(!empty($me)) {
                $cache = Cache::get($me);
                if($cache){
                    return json(['msg'=>'验证码请求过于频繁，请稍后再试！','code'=>2000,"vCode"=>$cache]);
                }else{
                    $user = new \app\admin\model\User();
                    $code = mt_rand(100000,999999);
                    Cache::set($me,$code,900);//缓存验证码15分钟
                    // 手机
                    if ($isMobile == 1 && $isEmail == 0) {
                        if(!empty($me) && !empty($code)){
                            $appid = SMS_ID;
                            $appkey = SMS_SECRET;
                            $templateId = 493640;
                            $smsSign = "深圳市伦茨科技";
                            //指定模板单发短信
                            try {
//                    $ssender = new SmsSingleSender($appid, $appkey);
//                    $params = [$scode];      //参数列表 验证码
//                    $result = $ssender->sendWithParam("86", $phone, $templateId,$params, $smsSign, "", "");
//                    $rsp = json_decode($result,true);
                                //return json(['msg'=>'验证码已发送，请注意查收！','code'=>200]);
                                return json(['msg'=>'验证码已发送，请注意查收！','code'=>200,"vCode"=>$code]);
                            } catch(\Exception $e) {
                                //echo var_dump($e);
                                return json(['msg'=>'验证码发送失败，请联系管理员！','code'=>402,"vCode"=>""]);
                            }
                        }
                        return json(['msg'=>'手机号不可为空','code'=>401,"vCode"=>""]);
                    }
                    // 邮箱
                    if ($isMobile == 0 && $isEmail == 1) {
                        //return json(['msg'=>'验证码已发送，请注意查收！','code'=>200,"vCode"=>"$code"]);
                        $toemail = $me;//收件人的邮箱
                        $mail = new PHPMailer();
                        $mail->isSMTP();// 使用SMTP服务
                        $mail->CharSet = "utf8";// 编码格式为utf8，不设置编码的话，中文会出现乱码
                        $mail->Host = "smtp.qq.com";// 发送方的SMTP服务器地址
                        $mail->SMTPAuth = true;// 是否使用身份验证
                        $mail->Username = "571247942@qq.com";// 发送方的163邮箱用户名，就是你申请163的SMTP服务使用的163邮箱</span><span style="color:#333333;">
                        $mail->Password = "rcyjoblabouobbib";// 发送方的邮箱密码，注意用163邮箱这里填写的是“客户端授权密码”而不是邮箱的登录密码！</span><span style="color:#333333;">
                        $mail->SMTPSecure = "ssl";// 使用ssl协议方式</span><span style="color:#333333;">
                        $mail->Port = 465;// 163邮箱的ssl协议方式端口号是465/994
                        $mail->setFrom("571247942@qq.com", "我是发件人，小哥");// 设置发件人信息，如邮件格式说明中的发件人，这里会显示为Mailer(xxxx@163.com），Mailer是当做名字显示
                        $mail->addAddress($toemail, '我是收件人，接收');// 设置收件人信息，如邮件格式说明中的收件人，这里会显示为Liang(yyyy@163.com)
                        $mail->addReplyTo("571247942@qq.com", "Reply");// 设置回复人信息，指的是收件人收到邮件后，如果要回复，回复邮件将发送到的邮箱地址
                        //$mail->addCC("xxx@163.com");// 设置邮件抄送人，只写地址(这个人也能收到邮件)
                        //$mail->addBCC("xxx@163.com");// 设置秘密抄送人(这个人也能收到邮件)
                        //$mail->addAttachment("bug0.jpg");// 添加附件
                        $mail->Subject = "邮件标题测试";// 邮件标题
                        // $code 邮箱验证码封存，可以改为数组生成随机验证码
                        $mail->Body = "小宝提醒您，您的验证码是：<strong>". $code." </strong>，请注意查收，验证码在15分钟内是有效的，短时间内请勿多次获取，感谢您的到来，祝您生活愉快！";// 邮件正文
                        //$mail->AltBody = "This is the plain text纯文本";// 这个是设置纯文本方式显示的正文内容，如果不支持Html方式，就会用到这个，基本无用
                        if (!$mail->send()) {// 发送邮件
                            //echo "验证码发送失败" . $mail->ErrorInfo;
                            return json(['msg'=>'验证码发送失败，请联系管理员！','code'=>402,"vCode"=>"","err"=> $mail->ErrorInfo]);
                        } else {
                            return json(['msg'=>'验证码已发送，请注意查收！','code'=>200,"vCode"=>""]);
                        }
                    }
                }
            }
        }catch (Exception $e){
            return json(['msg'=>'出错了！','code'=>400,"vCode"=>""]);
        }
    }
    // 校验_短信/邮箱_验证码（注册）
    public function checkRegCode(){
        $code = input("inputMeCode");
        $me = input('mobileEmail');
        $cacheCode = Cache::get($me);
        if($cacheCode == $code){
            return json(["error"=>0,"msg"=>"校验成功"]);
        }else{
            return json(["error"=>1,"msg"=>"校验失败"]);
        }
    }

    // 登录_账号/手机号/邮箱、密码 日志记录
    public function loginApi(){
        $param = input("username");//该参数可用户名、手机号、邮箱
        $paswd = input("password");
        if($param && $paswd){
            try{
                $tools = new Tools();
                $user = new \app\admin\model\Mag();
                $val = $user::whereOr("name",$param)->whereOr("phone",$param)->whereOr("email",$param)->value("password");
                //return json(["msg"=>"验证！", "code"=>200, "url"=>$val);
                //$val = Db::name("mag_user")->whereOr("name",$param)->whereOr("phone",$param)->whereOr("email",$param)->value("password");
                if($val){
                    $decryptPswd = $tools ->decrypt($val); // 解密
                    if($decryptPswd == $paswd){
                        $tk = $tools -> createToken($param); // 创建token
                        //Session::set("petPermissionToken",$tk); // session
                        Cookie::set('petPermissionToken', $tk, TOKEN_TIME_HALF_YEAR); //三个月

                        $user::where("name",$param)->update(["updated"=>time()]); //更新时间
                        // 记录日志
                        $logData = "msg=密码登录，name=".$param."，date=".date("Y-m-d H:i:s")."；\n";
                        $logPath = $tools ->createLogFile();
                        $logFile = fopen($logPath,"a");
                        fwrite($logFile,$logData);
                        fclose($logFile);

                        return json(["msg"=>"登录成功！", "code"=>200, "url"=>"/admin/bg","token"=>$tk]);
                    }else{
                        return json(["msg"=>"密码错误！", "code"=>402, "url"=>""]);
                    }
                }
                return json(["msg"=>"用户名不存在！","code"=>401,"url"=>""]);
            }catch (Exception $e){
                return json(["msg"=>"出错了！","code"=>400,"url"=>$e]);
            }
        }
        return json(["msg"=>"请求参数不可为空！","code"=>400]);
    }
    // 校验_手机号 =》页面验证码
    public function checkMobile(){
        $mobile = input("mobile");//手机号
        if($mobile){
            $user = new \app\admin\model\User();
            $val = $user::where("phone",$mobile)->value("phone");
            if($val){
                $pageCode = mt_rand(100000,999999);
                return json(["error"=>0,"msg"=>"校验成功！","pageCode"=>$pageCode]);
            }
            return json(["error"=>1,"msg"=>"校验失败，手机号错误！"]);
        }
        return json(["error"=>1,"msg"=>"手机号不可为空！"]);
    }
    // 校验_页面验证码
    public function checkPageCode(){
        $code = input('pageCode');
        $pageCode = input('inputPageCode');
        if($pageCode == $code){
            return json(["error"=>0,"msg"=>"校验成功"]);
        }else{
            return json(["error"=>1,"msg"=>"校验失败"]);
        }
    }
    // 验证码_短信（登录）
    public function codeSms(){
        try{
            $phone = input('mobile');
            $cache = Cache::get($phone);
            if($cache){
                return json(['msg'=>'验证码请求过于频繁，请稍后再试！','code'=>2000,"vCode"=>$cache]);
            }else{
                $mag = new \app\admin\model\Mag();
                $ph = $mag::where('phone',$phone)->value("phone");
                $code = mt_rand(100000,999999);
                Cache::set($phone,$code,900);//缓存验证码15分钟
                if(!empty($ph) && !empty($code)){
                    //return json(['msg'=>'短信验证码','status'=>'success','code'=>$sCode]);
                    $appid = SMS_ID;
                    $appkey = SMS_SECRET;
                    $templateId = 493640;
                    $smsSign = "深圳市伦茨科技";
                    //指定模板单发短信
                    try {
//                    $ssender = new SmsSingleSender($appid, $appkey);
//                    $params = [$scode];      //参数列表 验证码
//                    $result = $ssender->sendWithParam("86", $phone, $templateId,$params, $smsSign, "", "");
//                    $rsp = json_decode($result,true);
                        //return json(['msg'=>'验证码已发送，请注意查收！','code'=>200]);
                        return json(['msg'=>'验证码已发送，请注意查收！','code'=>200,"vCode"=>$code]);
                    } catch(\Exception $e) {
                        //echo var_dump($e);
                        return json(['msg'=>'验证码发送失败，请联系管理员！','code'=>402,"vCode"=>""]);
                    }
                }
                return json(['msg'=>'手机号不可为空','code'=>401,"vCode"=>""]);
            }
        }catch (Exception $e){
            return json(['msg'=>'出错了！','code'=>400,"vCode"=>""]);
        }
    }
    // 校验_短信验证码
    public function checkSmsCode(){
        $code = input("inputSmsCode");
        $phone = input('mobile');
        $cacheCode = Cache::get($phone);
        if($cacheCode == $code){
            return json(["error"=>0,"msg"=>"校验成功"]);
        }else{
            return json(["error"=>1,"msg"=>"校验失败"]);
        }
    }
    // 登录_手机号、验证码 日志记录
    public function loginMobileCode(){
        if(request()->isPost()){
            $phone = input('mobile');
            $code = input("inputSmsCode");
            if(empty($phone) || empty($code)){
                return json(["msg"=> "手机号/验证码为空","code"=> 4002]);
            }else{
                $cacheCode = Cache::get($phone);
                //查询用户是否存在
                $mag = new \app\admin\model\Mag();
                $users = $mag::field("name,phone")->where("phone",$phone)->where("status",1)->find();
                if(empty($users["phone"])){
                    return json(["msg"=>"该手机号不存在","code"=> 4003]);
                }else{
                    if($code != $cacheCode){
                        return json(["msg"=>"验证错误！","code"=> 4004]);
                    }else{
                        $tools = new Tools(); // 工具类
                        $tk = $tools -> createToken($users["name"]);// 创建 token
                        //Session::set("petPermissionToken",$tk);
                        Cookie::set('petPermissionToken', $tk, TOKEN_TIME_HALF_YEAR); //三个月

                        $mag::where("phone",$phone)->update(["updated"=>time()]); //更新时间
                        // 记录日志
                        $logData = "msg=验证码登录，name=".$phone."，date=".date("Y-m-d H:i:s")."；\n";
                        $logPath = $tools ->createLogFile();
                        $logFile = fopen($logPath,"a");
                        fwrite($logFile,$logData);
                        fclose($logFile);

                        return json(["msg"=>"登录成功","code"=>200,"token"=> $tk, "url"=>"/admin/bg"]);
                    }
                }
            }
        }
        return json(["msg"=>"请求错误！","code"=> 4001]);
    }
}