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
        'payment' => [
            'merchant_id'   => config_item('customer_wechat_payment_merchant_id'),
            'key'           => config_item('customer_wechat_payment_key'),
            'cert_path'     => config_item('customer_wechat_payment_cert_path'),
            'key_path'      => config_item('customer_wechat_payment_key_path'),
        ],
        'guzzle' => [
            'timeout' => 3.0,
        ]
    );
}

/**
 * 获取员工端微信的配置
 */
function getEmployeeWechatConfig($debug = true)
{
    return array(
        'debug'     => $debug,
        'app_id'    => EMPLOYEE_WECHAT_APPID,
        'secret'    => EMPLOYEE_WECHAT_SECRET,
        'token'     => EMPLOYEE_WECHAT_TOKEN,
        'aes_key'   => EMPLOYEE_WECHAT_AES_KEY,
        'log' => [
            'level' => 'debug',
            'file'  => APPPATH.'cache/wechat.log',
        ],
        'oauth' => [
            'scopes'   => [EMPLOYEE_WECHAT_OAUTH_SCOPES],
            'callback' => site_url('callback'),
        ],
        'payment' => [
            'merchant_id'        => '',
            'key'                => '',
            'cert_path'          => '',
            'key_path'           => '',
        ],
        'guzzle' => [
            'timeout' => 3.0,
        ]
    );
}