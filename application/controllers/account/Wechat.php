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
        log_message('error','调用登陆');

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
            $this->api_res(1006);
            return false;
        }

        $access_token   = $user['access_token'];
        $refresh_token  = $user['refresh_token'];
        $openid         = $user['openid'];
        $unionid        = $user['unionid'];
        $info_url   = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN';

        $user_info  = $this->httpCurl($info_url,'get','json');
        if(array_key_exists('errcode',$user_info)){
            log_message('error','请求info:'.$user_info['errmsg']);
            $this->api_res(1006);
            return false;
        }

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
            $customer->uxid         = $customer->max('id')+1;
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
            log_message('error','LOGIN登陆正常');
            $this->api_res(0,['token'=>$token]);
        }else{
            $this->api_res(1009);
        }
    }

    public function test(){
        $this->load->model('customermodel');
        $this->load->model('couponmodel');
        $this->load->model('coupontypemodel');

        $customer = Customermodel::where('openid','ob4npwqKrqc1TRYkJNpp0ll2vD4k')->first();
        var_dump($customer);
//        if(isset($customer)||!empty($customer)){
//            $data = ['customer_id'=>$customer->id,
//                'coupon_type_id'=>39
//            ];
////
////            //判断这个用户是否有优惠券gir
//            $sum =  Couponmodel::where($data)->get();
//            if(empty($sum)){
//
////                //发送优惠券
//                $coupon = Coupontypemodel::where('id',39)->first();
//                $update_coupon = [
//                    'customer_id'=>$customer->id,
//                    'coupon_type_id' => 39,
//                    'status' => 'unused',
//                    'deadline' => $coupon->deadline
//                ];
//                var_dump($update_coupon);
//                $activity = new Couponmodel();
//                $activity->fill($update_coupon);
//                $res=$activity->save();
//                var_dump($res);
////                if($res){
////                    $a='123123';
////                }else{
////                    $a='456456';
////                }
////                //发送二维码
//            }
//        }
    }

}
