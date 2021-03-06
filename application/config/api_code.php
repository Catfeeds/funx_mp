<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['api_code'] = [
    0     => '正确',
    500   => '内部错误',

    //公共返回信息
    1001  => '无效token,请重新登录',
    1002  => '表单验证未通过',
    1003  => '没有找到该用户',
    1004  => '文件上传失败',
    1005  => '没有输入查询所需要的必要信息',
    1006  => '登陆出错',
    1007  => '没有查询到记录',
    1008  => '查询到有重复的记录',
    1009  => '操作数据库出错',

    //普通返回信息

    //登陆相关
    10007 => '用户频繁发送短信',
    10008 => '短信验证码不匹配',

    //办理入住
    10010 => '手机号码跟住户号码不匹配',
    10011 => '验证码不匹配',
    10013 => '住户与当前用户不匹配',
    10014 => '请检查房间状态',
    10015 => '您已经签署过合同!',
    10016 => '已经有生成的合同!',
    10021 => '跳转到签署页面!',
    //payment支付
    10017 => '未找到订单信息!',
    10018 => '订单金额为零,无法发起支付!!',
    10019 => '该房间已经退过房了!',
    10020 => '该门店不支持线上支付!',
    10022 => '请使用办理时使用的账号进行该操作！',
    10100 => '库存不足',
    //活动相关
    11001 => '不在活动时间内',
    11002 => '超过抽奖次数限制',
    11003 => '谢谢参于',
    11004 => '不符合抽奖条件',
    11005 => '请先登录',
    11006 => '活动已下架',

    10023 => '请不要频繁提交',

];
