<?php
/**
 * Created by PhpStorm.
 * User: 57124
 * Date: 2021/11/22
 * Time: 17:06
 */

namespace app\admin\util;


use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;

class Tools{
    //1、Token 的生成
    public function createToken($username = null){
        $key = md5("xr571247942@qq");
        $payload=array(
            "iss"=>"逸曦穆泽",   //签发组织
            "aud"=>"happyacomma",//签发作者
            'appId'=> "571247942",
            'iat'=> time(),
            'exp'=> time() + TOKEN_TIME_HALF_YEAR, //一天：86400  七天：604800  一年366：31622400
            "username" => $username,
        );
        $jwt = new JWT();
        $token = $jwt::encode($payload,$key,'HS256');
        return $token;
    }
    //2、Token 的验证 (登录验证需要)
    function checkToken($token){
        $key = md5("xr571247942@qq");
        try{
            $res = json_encode(JWT::decode($token,$key,['HS256']));
            $arr = json_decode($res,true);
            if(!empty($arr["username"])){
                return ["msg"=>"Token通过","status"=>"success","username"=>$arr["username"],"code"=>200];
            }else{
                return ["msg"=>"当前用户不存在","status"=>"fail","code"=>401];
            }
        }catch (SignatureInvalidException $e){
            return ["msg"=>"Token无效","status"=>"fail","code"=>405,"url"=>"/auth/admin/login"];
        }catch (ExpiredException $e) {
            return ["msg"=>"Token过期","status"=>"fail","code"=>406,"url"=>"/auth/admin/login"];
        }
    }

    //删除文件 重写unlink方法
    public function unlink($path){
        return is_file($path) && unlink($path);
    }
    //创建文件夹
    protected function dirFile($name){
        $dirName = "public/".$name;
        if(!file_exists($dirName)){
            mkdir($dirName,0777,true);
        }
        return $dirName;
    }

    // 创建自定义日志文件
    public function createLogFile(){
        //$filePath = App::getRootPath()."public/logs/".date("Ym").".log";
        $filePath = getcwd()."/logs/".date("Ym").".log";
        if(!file_exists($filePath)){
           fopen($filePath,"w");
        }
        return $filePath;
    }
    /**
     * 产生六位（0-9、a-z混合）的随机数
     */
    public function getRand($num){
        if($num < 3){
            $num = 3;
        }
        $letters = range('a', 'z');
        $arr = array_merge(range(0, 9), $letters);
        shuffle($arr);//打乱数组
        $str = '';
        $len = count($arr);
        for ($i = 0; $i < $num; $i++){
            $rand = mt_rand(0, $len - 1);//mt_rand() 比rand() 快四倍
            $str .= $arr[$rand];
        }
        return $str;
    }
    //新上传文件
    public function upload($file,$name){
        $dirName = $this->dirFile($name);
        if ($file["error"]) {
            return null;
        }else {
            $fileName = $dirName . "/" . $file["name"]; //拼接文件路径
            //判断文件是否存在
            if (file_exists($fileName)) {
                return '/'.$fileName;
            } else {
                //保存文件 ($_FILES["文件名"]["tmp_name"],newPath)，（旧路径,新路径）
                move_uploaded_file($file["tmp_name"], $fileName);
                return '/'.$fileName;
            }
        }
    }
    //上传更新文件
    public function uploadUpd($file,$name,$oldFile){
        $dirName = $this->dirFile($name);
        if ($file["error"]) {
            return null;
        }else {
            $fileName = $dirName . "/" . $file["name"]; //拼接文件路径
            //判断文件是否存在
            if (file_exists($fileName)) {
                return '/'.$fileName;
            } else {
                move_uploaded_file($file["tmp_name"], $fileName);
                unset($file);
                if($oldFile) {
                    $oldName = strstr($oldFile, "public"); //切割字符串
                    $this->unlink($oldName); //删除旧文件
                }
                return '/'.$fileName;
            }
        }
    }

    //加载跳转
    public function loadResult($url='',$msg=""){
        $str='<script type="text/javascript" src="/public/static/js/jquery-3.5.1.min.js"></script>';
        $str.='<script>$(function(){setTimeout(function(){window.location.href="'.$url.'"},500)});</script>';
        return $str;
    }

    // 加密
    public function encrypt($str){
        $method = "DES-ECB";
        $key ="happyacomma";
        $res = openssl_encrypt($str, $method, $key);
        return $res;
    }
    // 解密
    public function decrypt($str){
        $method = "DES-ECB";
        $key ="happyacomma";
        $res = openssl_decrypt($str, $method, $key);
        return $res;
    }
}