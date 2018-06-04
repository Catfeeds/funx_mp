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
        $filed = ['coupon_type_id','status','deadline'];
        $coupon = Couponmodel::with('coupontype')->orderBy('created_at','DESC')
                            //->where('resident_id',CURRENT_ID)
                            ->get($filed);
        $this->api_res(0,$coupon);
    }

}