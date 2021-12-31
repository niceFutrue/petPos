<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\facade\Route;

Route::rule('clearRuntime','VerifyBefore/clearRuntime');      // 清理缓存


// 验证前操作
Route::rule('register','VerifyBefore/registerApi');           // 注册API
Route::rule('checkName','VerifyBefore/checkName');            // 校验_账号名（注册）
Route::rule('checkPhoneEmail','VerifyBefore/checkPhoneEmail');// 校验_手机号/邮箱（注册）
Route::rule('codeRegister','VerifyBefore/codeRegister');      // 验证码_短信/邮箱（注册）
Route::rule('checkRegCode','VerifyBefore/checkRegCode');      // 校验_短信/邮箱_验证码（注册）

Route::rule('login','VerifyBefore/loginApi');                 // 登录_账号/手机号/邮箱、密码
Route::rule('checkMobile','VerifyBefore/checkMobile');        // 校验_手机号 =》页面验证码
Route::rule('checkPageCode','VerifyBefore/checkPageCode');    // 校验_页面校验码
Route::rule('codeSms','VerifyBefore/codeSms');                // 验证码_短信（登录）
Route::rule('checkSmsCode','VerifyBefore/checkSmsCode');      // 校验_短信验证码
Route::rule('loginMobileCode','VerifyBefore/loginMobileCode');// 登录_手机号、验证码
// 验证操作
Route::rule('registerPage','index/register');          // 页面_注册
Route::rule('loginPage','index/login');                // 页面_登录
Route::rule('logout','index/logout');                  // 注销
// 验证后操作

Route::get('test', 'index/test');
Route::rule('bg','Index/admin');

Route::rule('rssDel','Mag/rssDel'); // 数据删除 (统一接口) rss_
Route::rule('rssGet','Mag/rssGet'); // 数据获取 (统一接口) rss_
Route::rule('sourceMag','Mag/sourceMag');   // 订阅源管理
Route::rule('sourceApd','Mag/sourceApd');   // 订阅源新增、更新
Route::rule('rssData','Mag/rssData');       // 订阅内容获取
Route::rule('contentMag','Mag/contentMag'); // 订阅内容管理
Route::rule('contentApd','Mag/contentApd'); // 订阅内容新增、更新

Route::rule('delData','Mag/delData'); // 数据删除 (统一接口) mag_
Route::rule('getData','Mag/getData'); // 数据获取 (统一接口) mag_
Route::rule('seoMag','Mag/seoPage');
Route::rule('seoApd','Mag/seoApd');