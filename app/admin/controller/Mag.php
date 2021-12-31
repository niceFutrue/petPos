<?php
/**
 * Created by PhpStorm.
 * User: 57124
 * Date: 2021/11/22
 * Time: 14:11
 */

namespace app\admin\controller;


use app\BaseController;
use think\facade\Db;
use think\facade\View;

class Mag extends BaseController {

    protected $middleware = ['app\middleware\Check'];

    // 资讯来源管理
    public function sourceMag(){
        return View::fetch();
    }
    //资讯订阅源新增、更新
    function sourceApd(){
        $name = input('name');
        if(request()->isPost() || !empty($name)){
            $time = date('Y-m-d H:i:s', time()); //数据新增日期
            $id = input('id');
            //传入参数id判断： 无id 则新增 、 有id 则更新
            $data = array(
                'name'      => $name,
                'url'       => input('url'),
                'updated'   => $time,
            );
            if(empty($id)){
                Db::table("rss_url")->insert($data);
                return json(["msg"=>'添加成功！']);
            }else{
                Db::table("rss_url")->where('id',$id)->update($data);
                return json(["msg"=>'更新成功！']);
            }
        }
    }

    // 资讯管理
    public function contentMag(){
        return View::fetch();
    }
    //删除文件 重写unlink方法
    public function unlink($path){
        return is_file($path) && unlink($path);
    }
    //对应的文件夹
    public function dirFile($name){
        $dirName = "store/".$name."/".date("Y");
        if(!file_exists($dirName)){
            mkdir(app()->getRootPath().'public/'.$dirName,0777,true);
        }
        return $dirName;
    }
    //图片对象生成
    protected function buildImg($files,$oldfile=null){
        $dirName = $this -> dirFile('info');
        if($files && preg_match('/^(data:\s*image\/(\w+);base64,)/', $files,$result)){
            //旧文件不为空，则删除
            if($oldfile){
                $oldName = strstr($oldfile, "store"); //切割字符串
                $this ->unlink($oldName); //删除旧文件
            }
            $fileExt1 = $result[2];
            //设置图片生成的名字
            $imageName = time() . rand("100", "999") . ".$fileExt1";
            //判断是否有逗号，有就截取后半部分
            if (strstr($files, ",")) {
                $files = explode(',',$files);
                $files = $files[1];
            }
            //拼接路径和图片名称
            $imageSrc = $dirName."/" . $imageName;
            //生成图片 返回字节数
            file_put_contents($imageSrc,base64_decode($files)); //data:image/jpeg;base64, 拼接Base64
            //返回图片路径
            return "/" .$imageSrc;
        }
        return "";
    }
    //资讯内容新增、更新
    function contentApd(){
        $tle = input('rssName');
        if(request()->isPost() && !empty($tle)){
            $id = input('id');
            $imgSrc = input('rssSrc');
            if(empty($id)){
                $data = array(
                    'title'     => $tle,
                    'img_src'   => $imgSrc,
                    "source_name"=>input("rssSourceName"),
                    'keywords'  => input('rssKeywords'),
                    'description'=> input('rssDescription'),
                    'content'   => input('rssContent'),
                    'source_link'=> input('rssLink'),
                    'status'    => 1,
                    'updated'   => input("rssDate"),
                );
                Db::table("rss_info")->insert($data);
                return json(["msg"=>'添加成功！']);
            }else{
                $data = array(
                    'title'     => $tle,
                    'img_src'   => $imgSrc,
                    'keywords'  => input('rssKeywords'),
                    'description'=> input('rssDescription'),
                    'content'   => input('rssContent'),
                    'status'    => input("status"),
                );
                Db::table("rss_info")->where('id',$id)->update($data);
                return json(["msg"=>'更新成功！']);
            }
        }
        return json(["msg"=>'出错了！','code'=>400]);
    }

    //订阅源文章内容
    public function detail(){
        if($this->request ->post()){
            $link = input('link');
            if(!empty($link)){
                $content = Db::name('rss_info')->field('title,content,link')->whereLike('link',$link.'%')->find();
                return json($content);
            }else{
                return json([ 'error'=>'参数出错了']);
            }
        }
        return json([ 'error'=>'方法出错了']);
    }
    //订阅源 -> 数据添加、获取
    public function rssData(){
        $rssId = input("id");
        $url = input("url");
        if(request()->isPost()){
            $buff = "";
            //打开rss地址，并读取，读取失败则中止
            $fp = fopen($url, "r") or die("无法打开该网站Feed");
            while (!feof($fp)) {
                $buff .= fgets($fp, 4096);
            }
            fclose($fp);
            $parser = xml_parser_create();
            xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
            xml_parse_into_struct($parser, $buff, $values, $idx);
            xml_parser_free($parser);
            //return json(["code"=>400,"msg"=>$values]);
            $is_item = 0; $title = "";$link = "";$description = "";$content = "";$updated=""; $arr = [];
            // 集微网（都有）  2、cnBeta.COM - 中文业界（缺description） 1、IT之家（缺content）
            foreach ($values as $val) {
                $tag = $val["tag"];
                $tag = strtolower($tag);//标签统一转为小写
                $type = $val["type"];
                if (($tag == "item" || $tag == "entry" )&& $type == "open"){
                    $is_item = 1;
                }else if (($tag == "item" || $tag == "entry" )&& $type == "close") {
                    //构造输出字符串
                    $data = array(
                        'title' => $title,
                        'content'=> $content,
                        'description'=>$description,
                        'link' => $link,
                        'date' => date('Y-m-d H:i:s', strtotime($updated)),
                    );
                    array_push($arr,$data);
                    $is_item = 0;
                }
                if(isset($val["value"])){
                    $value = $val["value"];
                }
                //仅读取item标签中的内容
                if($is_item == 1){
                    if ($tag == "title") {$title = $value;}
                    if ($tag == "link") {$link = $value;}
                    if($rssId == 1){
                        if ($tag =="description"){
                            $description = strip_tags(substr($value,0,strpos($value,'。')));
                            $content=$value;}
                    }elseif ($rssId==2){
                        if ($tag =="content:encoded" || $tag =="content"){
                            $description = strip_tags(substr($value,0,strpos($value,'。')));
                            $content=$value;
                        }
                    }else{
                        if ($tag =="description"){$description=$value;}
                        if ($tag =="content:encoded" || $tag =="content"){$content=$value;}
                    }
                    if ($tag =="pubdate" || $tag =="updated"){$updated=$value;}
                }
            }
            //输出结果
            return json(["code"=>200,"msg"=>"获取成功！","data"=>$arr]);
        }
        return json(["code"=>400,"msg"=>"请求错误！"]);
    }

    // seo 页面
    public function seoPage(){
        return View::fetch();
    }
    //seo 添加、更新
    public function seoApd(){
        if(request()->isPost()){
            $time = date('Y-m-d H:i:s', time()); //发布、更新时间
            $id = input('id');
            $data = array(
                'rule'         => input('rule'),
                'name'         => input('name'),
                'title'        => input('tle'),
                'keywords'     => input('keywords'),
                'description'  => input('description'),
                'status'       => input('status'),
                'release'      => $time,
                'updated'      => $time,
            );
            if(empty($id)){
                Db::table("mag_seo")->insert($data);
                return json(["msg"=>'新增成功！']);
            }else{
                Db::table("mag_seo")->where('id',$id)->update($data);
                return json(["msg"=>'更新成功！']);
            }
        }
        return json(["msg"=>'出错了']);
    }
    // 删除数据 (统一接口) mag_
    public function delData(){
        if($this->request->isPost()){
            $params = input('type');
            $id = input('id');
            if(!empty($id) && $params){
                Db::name('mag_'.$params)->where('id',$id)->delete();
                return json(["msg"=>"删除成功！"]);
            }else{
                return json(["msg"=>"型号不对！"]);
            }
        }
        return json(["msg"=>"请求错误！"]);
    }
    // 获取数据 (统一接口) mag_
    public function getData($type,$num = 10){
        if($this->request->isPost()){
            $res = Db::name("mag_".$type)->order("id","desc")->paginate($num);
            return $res;
        }
        return json(["msg"=>"请求错误！"]);
    }

    // 删除数据 (统一接口) rss_
    public function rssDel(){
        if($this->request->isPost()){
            $params = input('type');
            $id = input('id');
            if(!empty($id) && $params){
                Db::name('rss_'.$params)->where('id',$id)->delete();
                return json(["msg"=>"删除成功！"]);
            }else{
                return json(["msg"=>"型号不对！"]);
            }
        }
        return json(["msg"=>"请求错误！"]);
    }
    // 获取数据 (统一接口) rss_
    public function rssGet($type,$num = 10){
        if($this->request->isPost()){
            $res = Db::name("rss_".$type)->order("id","desc")->paginate($num);
            return $res;
        }
        return json(["msg"=>"请求错误！"]);
    }


    /**
     * 产生六位（0-9、a-z混合）的随机数
     */
    public function getRand($num){
        if($num < 3){$num = 3;}
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
}