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
            'account/wechat/login',
            'account/server/menu',
            'account/server/checkinorbookingevent',
            'account/server/index',

            'resident/order/getorderbynumber',

            'pay/payment/config',
            //微信支付回调
            'pay/payment/notify',

            'resident/resident/getresident',
            'resident/contract/sendsms',
            'resident/contract/confirm',
            'resident/contract/test1',

            'store/home/listhome',
            'store/store/mapconfig',

            'resident/resident/record',
            'resident/resident/refund',
            'resident/resident/checkout',

            'common/imageupload',
            'common/fileupload',

            'store/store/showcity',
            'store/store/showstore',
            'store/store/liststore',
            'store/store/get',
            'store/roomtype/get',

            'service/servicetype/servicetype',
            'service/serviceorder/order',
            'service/serviceorder/serviceorder',
            'service/serviceorder/cleanorder',
            'service/reserve/reserve',
            'service/reserve/precontract',
            'service/reserve/visited',

            'customer/center/showinfo',
            'customer/center/shownickname',
            'customer/center/setnickname',
            'customer/center/setphone',
            'customer/center/verifyphone',

            'customer/contract/checksign',
            'customer/contract/generate',

            'shop/goodscategory/listgoods',
            'shop/goods/goodsinfo',
            'shop/goods/searchgoods',
            'shop/goods/goodssta',
            'shop/goodsaddress/addaddress',
            'shop/goodsaddress/deleteaddress',
            'shop/goodsaddress/updateaddress',
            'shop/goodscart/cart',
            'shop/goodscart/deletecart',
            'shop/goodsaddress/listaddress',
            'shop/goodscart/listcart',
            'shop/goodscart/deletecart',
            'shop/goodscart/addcart',
            'shop/goodscart/quantityincre',
            'shop/goodscart/quantitydecre',
            'shop/goodscart/quantitynum',
            'shop/goodscart/accounts',
            'shop/contract/contract',
            'shop/goodscart/getorder',
            'shop/goodscart/nowbuy',
            'shop/order/orderlist',
            'shop/order/orderux',
            'shop/order/order',


            'coupon/coupon/listcoupon',
            'coupon/coupon/coupon',

            'smartlock/smartlock/rooms',
            'smartlock/smartlock/withsmart',
            'smartlock/smartlock/temporarypwd',
        );

        $directory  = $this->CI->router->fetch_directory();
        $class      = $this->CI->router->fetch_class();
        $method     = $this->CI->router->fetch_method();
        $full_path  = strtolower($directory.$class.'/'.$method);
        // var_dump( $full_path );
        if(!in_array($full_path,$authArr)) {

            try {

                $token = $this->CI->input->get_request_header('token');
                $decoded = $this->CI->m_jwt->decodeJwtToken($token);
                $d_uxid   = $decoded->uxid;
                $d_company_id   = $decoded->company_id;
                define('CURRENT_ID',$d_uxid);
                define('COMPANY_ID',$d_company_id);
            } catch (Exception $e) {
                header("Content-Type:application/json;charset=UTF-8");
                echo json_encode(array('rescode' => 1001, 'resmsg' => 'token无效', 'data' => []));
                exit;
            }
        }
    }
}