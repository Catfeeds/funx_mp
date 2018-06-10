<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use EasyWeChat\Foundation\Application;
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/16 0016
 * Time:        16:18
 * Describe:
 */
class Wechat extends MY_Controller
{
    protected $app;
    protected $oauth;

    const TARGET_URL = 'target_url';

    public function __construct()
    {
        parent::__construct();
        //$this->app = new Application($this->getCustomerWechatConfig());
    }

    public function login()
    {
        $post   = $this->input->post(null,true);

        //先传一个定值1
        $company_id = $post['company_id'];
        $code   = $post['code'];
        $appid  = config_item('wx_map_appid');
        $secret = config_item('wx_map_secret');
        $url    = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$appid.'&secret='.$secret.'&code='.$code.'&grant_type=authorization_code';
        $user   = $this->httpCurl($url,'get','json');
        if(array_key_exists('errcode',$user))
        {
            log_message('error','请求access_token'.$user['errmsg']);
            $this->api_res(1006);
            return false;
        }
        log_message('error','---->'.json_encode($user));
        $access_token   = $user['access_token'];
        $refresh_token  = $user['refresh_token'];
        $openid         = $user['openid'];
        $unionid        = $user['unionid'];
        $info_url   = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN';
        log_message('error','---->'.$info_url);
        $user_info  = $this->httpCurl($info_url,'get','json');
        if(array_key_exists('errcode',$user_info)){
            log_message('error','请求info:'.$user_info['errmsg']);
            $this->api_res(1006);
            return false;
        }
        log_message('error','----->'.json_encode($user_info));
        $nickname   = $user_info['nickname'];
        $gender     = $user_info['sex'];
        $province   = $user_info['province'];
        $city       = $user_info['city'];
        $country    = $user_info['country'];
        $avatar     = $user_info['headimgurl'];
        $this->load->model('customermodel');
        if(Customermodel::where(['company_id'=>$company_id,'openid'=>$openid])->exists()){
            $customer   = Customermodel::where(['company_id'=>$company_id,'openid'=>$openid])->first();
        }else{
            $customer   = new Customermodel();
            //需要核实修改
            $customer->uxid         = $customer->id;
            $customer->company_id   = $company_id;
            $customer->openid       = $openid;
        }
            $customer->nickname     = $nickname;
            $customer->gender       = $gender;
            $customer->province     = $province;
            $customer->city         = $city;
            $customer->country      = $country;
            $customer->avatar       = $avatar;
        if($customer->save())
        {
            $this->load->library('m_redis');
            $this->load->library('m_jwt');
            $token  = $this->m_jwt->generateJwtToken($customer->uxid,$customer->company_id);
            $this->m_redis->storeCustomerInfo($customer->uxid,$customer->toJson());
            $this->api_res(0,['token'=>$token]);
        }else{
            $this->api_res(1009);
        }
    }

}
