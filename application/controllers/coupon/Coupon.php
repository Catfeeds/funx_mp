<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/6/2
 * Time:        9:27
 * Describe:    优惠券
 */
class Coupon extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('couponmodel');
    }

    /**
     * 优惠券列表
     */
    public function listCoupon()
    {
        $this->load->model('Coupontypemodel');
        $filed = ['resident_id','coupon_type_id','status','deadline'];
        $coupon = Couponmodel::with('coupontype')->orderBy('created_at','DESC')->where('resident_id',CURRENT_ID)
                ->get($filed)->map(function ($coupon){
                    $coupon = $coupon->toArray();
                    $coupon['deadline'] = date('Y-m-d',strtotime($coupon['deadline']));
                    return $coupon;
                })->toArray();
        $this->api_res(0,$coupon);
    }

    /**
     * 优惠券使用
     */
    /*public function coupon()
    {
        $post = $this->input->post(null,true);
        if($post['status']){
            $status = trim($post['status']);
        }else{
            $status = 'UNUSED';
        }
        $this->load->model('Coupontypemodel');
        $filed = ['coupon_type_id','status','deadline'];
        $coupon = Couponmodel::with('coupontype')->where('status',$status)
                ->orderBy('created_at','DESC')
                ->get($filed)->map(function ($coupon){
                    $coupon = $coupon->toArray();
                    $coupon['deadline'] = date('Y-m-d',strtotime($coupon['deadline']));
                    return $coupon;
                })->toArray();
        $this->api_res(0,$coupon);
    }*/
}