<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Author:      weijinlong
 * Date:        2018/4/8
 * Time:        09:11
 * Describe:    授权登录token验证Hook
 */

class AuthHook {

    private $CI;

	public function __construct()
  	{
        $this->CI = &get_instance();   //获取CI对象
    }

    public function isAuth()
    {
        //免登录白名单
        //格式1 目录/类/方法
        //格式2 类/方法
        //注意，所有url统一用小写，不要大写
        $authArr = array(
            'ping/index',
            'account/wechat/login',
            'account/wechat/test',
            'account/server/menu',
            'account/server/checkinorbookingevent',
            'account/server/index',
            'resident/order/getorderbynumber',
//            'pay/payment/config',
            //微信支付回调
            'pay/payment/notify',
            //合同签章结果
            'resident/contract/signresult',
//            'resident/contract/resigncontract',
//            'resident/contract/resignfddurl',
            'resident/resident/getresident',
            'resident/contract/index',
            'resident/contract/sendsms',
            'resident/contract/confirm',
//            'resident/contract/signcontract',
//            'resident/contract/test',
            'store/home/listhome',
            'store/store/mapconfig',
//            'resident/resident/record',
            'resident/resident/refund',
            'resident/resident/checkout',
            'common/imageupload',
            'common/fileupload',

           //预定签约的一些回调
            'resident/reserve/contractconfirm',
            'resident/reserve/signresult',


            'shop/goodscategory/listgoods',
            'shop/goods/goodsinfo',
            'shop/goods/searchgoods',

            'shop/goodsaddress/addaddress',
            'shop/goodsaddress/deleteaddress',
            'shop/goodsaddress/updateaddress',

        );

        $directory  = $this->CI->router->fetch_directory();
        $class      = $this->CI->router->fetch_class();
        $method     = $this->CI->router->fetch_method();
        $full_path  = strtolower($directory.$class.'/'.$method);
        // var_dump( $full_path );
        if(!in_array($full_path,$authArr)) {
            try {
                $token = $this->CI->input->get_request_header('token');
                log_message('debug','TOKEN'.$token);
                $decoded = $this->CI->m_jwt->decodeJwtToken($token);
                $d_uxid   = $decoded->uxid;
                $d_company_id   = $decoded->company_id;
            
                $this->CI->current_id = $d_uxid;
                $this->CI->company_id = $d_company_id;
                
                //SaaS权限验证
                $this->saas();

                $this->CI->load->model('customermodel');
                log_message('debug','current_id='.get_instance()->current_id);
                $this->CI->user = Customermodel::where('uxid',get_instance()->current_id)->first();
            } catch (Exception $e) {
                log_message('error',$e->getMessage());
                header("Content-Type:application/json;charset=UTF-8");
                echo json_encode(array('rescode' => 1001, 'resmsg' => 'token无效', 'data' => []));
                exit;
            }
        }
    }

    //SaaS权限验证
    private function saas(){
       
        $company_id = get_instance()->company_id;

        if(!empty($company_id)){
            // if(!$this->CI->load->is_loaded('companymodel')){
                $this->CI->load->model('companymodel');
            // }
            $model = Companymodel::where('id',$company_id)->first();

            if(empty($model)){
                throw new Exception('该账号不存在');
            }

            //判断有效期
            if(strtotime($model->expiretime)<time()){
                throw new Exception('该账号已经过期失效，请续费');
            }

            //判断模块权限


            //判断状态
            if('CLOSE' === $model->status){
                throw new Exception('该账号已经注销，请联系管理员');
            }
        }
    }
}