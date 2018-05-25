<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/24 0024
 * Time:        18:34
 * Describe:
 */
/**
 * 客户端微信公众号配置
 */
function getCustomerWechatConfig(){
    $debug  = (ENVIRONMENT!=='development'?false:true);
    return array(
        'debug'     => $debug,
        'app_id'    => config_item('wx_map_appid'),
        'secret'    => config_item('wx_map_secret'),
        'token'     => config_item('wx_map_token'),
        'aes_key'   => config_item('wx_map_aes_key'),
        'log' => [
            'level' => 'debug',
            'file'  => APPPATH.'cache/wechat.log',
        ],
        //调用授权
        'oauth' => [
            'scopes'   => config_item('wx_customer_oauth_scopes') ,
            'callback' => config_item('wx_oauth_callback'),
        ],
        /*'payment' => [
            'merchant_id'   => CUSTOMER_WECHAT_PAYMENT_MERCHANT_ID,
            'key'           => CUSTOMER_WECHAT_PAYMENT_KEY,
            'cert_path'     => CUSTOMER_WECHAT_PAYMENT_CERT_PATH,
            'key_path'      => CUSTOMER_WECHAT_PAYMENT_KEY_PATH,
        ],*/
        'guzzle' => [
            'timeout' => 3.0,
        ]
    );
}