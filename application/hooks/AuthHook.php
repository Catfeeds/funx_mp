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

            'common/imageupload',
            'common/fileupload',

            'service/servicetype/servicetype',
            'service/serviceorder/serviceorder',
            'service/serviceorder/cleanservice',

            'shop/goodscategory/listgoods',
            'shop/goods/goodsinfo',
            'shop/goods/searchgoods',
<<<<<<< HEAD
            'shop/goodsaddress/addaddress',
            'shop/goodsaddress/deleteaddress',
            'shop/goodsaddress/updateaddress',
            'shop/goodscart/cart',
            'shop/goodscart/deletecart'
=======

            'basic/store/show',
            'basic/store/liststore',
            'basic/store/get',
            'basic/roomtype/get',

>>>>>>> 4f9b3148d8f8093f9b4815e7bc1a85df0efc3910

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
                $d_bxid   = $decoded->bxid;
                define('CURRENT_ID',$d_bxid);

            } catch (Exception $e) {
                header("Content-Type:application/json;charset=UTF-8");
                echo json_encode(array('rescode' => 1001, 'resmsg' => 'token无效', 'data' => []));
                exit;
            }
        }
    }
}