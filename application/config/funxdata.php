<?php

/**
 * funxdata的一些配置文件放在这，此文件添加到 .gitignore
 * 后期需要隐藏，不上传到版本库
 */


/*
|--------------------------------------------------------------------------
| 设置时区
|--------------------------------------------------------------------------
*/
date_default_timezone_set('Asia/ShangHai');
$config['time_zone']    = date_default_timezone_get();

/*
//梵响数据系统相关文件的整理配置
*/

//微信网页授权的信息
$config['wx_web_appid']     = 'wx75fd74e2316b2355';
$config['wx_web_secret']    = '70fa3a7fe658be97552788fc764f5434';

//云片信息
//https://sms.yunpian.com/v1/sms/send.json
$config['yunpian_api_url']  = 'http://sms.yunpian.com/v2/sms/single_send.json';
$config['yunpian_api_key']  = 'a91819aaea5b684dfb571442c279a9a3';

//jwt相关
$config['jwt_key'] = 'jfo1jf02jfoijf02klbm9&@Fklwfwef';
$config['jwt_alg'] = 'HS256';
$config['jwt_iss'] = 'http://example.org';
$config['jwt_exp'] = (time()+7200); //过期时间
//$config['jwt_nbf'] = 1357000000;


//上传附件的cdn地址
$config['cdn_path'] = 'http://tfunx.oss-cn-shenzhen.aliyuncs.com';

//智能设备相关配置信息
$config['yeeuuapiBaseUrl']  = 'https://api.yeeuu.com/v1/locks';
$config['yeeuualmsUrl']     = 'https://alms.yeeuu.com/apartments/synchronize_apartments';

$config['joyMeterClientId'] = 'joy000001';
$config['joyMeterApiUrl']   = 'http://122.225.71.66:8787/amr/joy/';
$config['joyPublicKeyPath'] = 'private/keys/rsa_public_key.pem';
$config['joyPrivateKeyPath']= 'private/keys/rsa_private_key.pem';
$config['joyLockPartnerId'] = '59cf50b69e23627437000028';
$config['joyLockSecret']    = 'fahwkc5M';

$config['danbayUserName']   = 'jindihuohua';
$config['danbayPassword']   = 'a123456';
$config['danbaymToken']     = '6nQIQ3EOpkaNtRly1M2MDoDjX7jxGjntldYv1JbsQB7srroptaUc3z2QypzDbgzc';

