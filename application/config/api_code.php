<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['api_code'] = [
    0       => '正确',
    500     => '内部错误',

    //公共返回信息
    1001    => '无效token,请重新登录',
    1002    => '表单验证未通过',
    1003    => '没有找到该用户',
    1004    => '文件上传失败',
    1005    => '没有输入查询所需要的必要信息',
    1006    => '登陆出错',
    1007    => '没有查询到记录',
    1008    => '查询到有重复的记录',
    1009    => '操作数据库出错',

    //普通返回信息

    //登陆相关
    10002   => '没有输入code',
    10003   => '没有输入手机号',
    10007   => '用户频繁发送短信',
    10008   => '短信验证码不匹配',


];