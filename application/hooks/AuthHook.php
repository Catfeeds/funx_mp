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
            'resident/contract/resigncontract',
            'resident/contract/resignfddurl',
            'resident/resident/getresident',
            'resident/contract/index',
            'resident/contract/sendsms',
            'resident/contract/confirm',
            'resident/contract/signcontract',
            'resident/contract/test',
            'store/home/listhome',
            'store/store/mapconfig',
//            'resident/resident/record',
            'resident/resident/refund',
            'resident/resident/checkout',
            'common/imageupload',
            'common/fileupload',
           /* 'store/store/showcity',
            'store/store/showstore',
            'store/store/liststore',*/
//            'store/store/get',
//            'store/roomtype/get',
//            'store/store/appoint',

//            'service/servicetype/servicetype',
            //'service/serviceorder/order',
//            'service/serviceorder/serviceorder',
//            'service/serviceorder/cleanorder',
            //'service/reserve/reserve',
//            'service/reserve/precontract',
//            'service/reserve/visited',

//            'customer/contract/checksign',
//            'customer/contract/generate',
            /*'customer/center/showinfo',
            'customer/center/shownickname',
            'customer/center/setnickname',
            'customer/center/setphone',
            'customer/center/verifyphone',*/

            'shop/goodscategory/listgoods',
            'shop/goods/goodsinfo',
            'shop/goods/searchgoods',
//            'shop/goods/goodssta',
            'shop/goodsaddress/addaddress',
            'shop/goodsaddress/deleteaddress',
            'shop/goodsaddress/updateaddress',
//            'shop/goodscart/cart',
//            'shop/goodscart/deletecart',
//            'shop/goodsaddress/listaddress',
//            'shop/goodscart/listcart',
//            'shop/goodscart/deletecart',
//            'shop/goodscart/addcart',
//            'shop/goodscart/quantityincre',
//            'shop/goodscart/quantitydecre',
//            'shop/goodscart/quantitynum',
//            'shop/goodscart/accounts',
//            'shop/contract/contract',
//            'shop/goodscart/getorder',
//            'shop/goodscart/nowbuy',
//            'shop/order/orderlist',
//            'shop/order/orderux',
//            'shop/order/order',
//            'shop/goods/numorder',
//            'resident/order/unpaid',
//            'resident/order/paid',
//            'resident/order/listunpaidorder',
//            'resident/order/listpaidorder',
//        ' resident/contract/watchcontract',
//        ' resident/resident/count',

//            'coupon/coupon/listcoupon',
//            'coupon/coupon/coupon',
              'service/reserve/template_message',
//            'smartlock/smartlock/rooms',
//            'smartlock/smartlock/getstore',
//            'smartlock/smartlock/withsmart',
//            'smartlock/smartlock/temporarypwd',
//            'smartlock/smartlock/updatepwd',
//            'smartlock/smartlock/lockrecord',

//            'owner/owner/ownerlist',
//            'owner/owner/bill',
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
                define('CURRENT_ID',$d_uxid);
                define('COMPANY_ID',$d_company_id);
                $this->CI->load->model('customermodel');
                log_message('debug','current_id='.CURRENT_ID);
                $this->CI->user = Customermodel::where('uxid',CURRENT_ID)->first();
            } catch (Exception $e) {
                header("Content-Type:application/json;charset=UTF-8");
                echo json_encode(array('rescode' => 1001, 'resmsg' => 'token无效', 'data' => []));
                exit;
            }
        }
    }
}