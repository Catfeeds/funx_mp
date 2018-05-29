<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use EasyWeChat\Foundation\Application;
use Carbon\Carbon;
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/28 0028
 * Time:        9:25
 * Describe:    订单
 */
class Order extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 订单列表
     */
    public function listOrder()
    {
//        $input  = $this->input->post(null,true);
//        $resident_id    = $input['resident_id'];
//        $this->load->model('residentmodel');
//        $resident   = Residentmodel::find($resident_id);
////        $this->checkUser($resident->uxid);
//        $this->load->model('ordermodel');
//        $orders = $resident->orders;

        $uxid   = CURRENT_ID;
        $this->load->model('ordermodel');
        $this->load->model('residentmodel');
        $residents  = Residentmodel::with('orders')->where('uxid',$uxid)->get();
        //$orders = Ordermodel::where('uxid',$uxid)->get();
    }

    /**
     * 通过订单编号和住户id获取用户订单信息
     */
    public function getOrderByNumber()
    {

        $input  = $this->input->post(null,true);
        $resident_id = $input['resident_id'];
        $number      = $input['number'];
        $this->load->model('ordermodel');
        $orders = Ordermodel::where(['resident_id'=>$resident_id,'number'=>$number])->get();
        if (0 == count($orders)) {
            $this->api_res(10017);
            return;
        }

        //计算总金额
        $amount = $orders->sum('money');
        if (0 == $amount) {
            $this->api_res(10018);
            return;
        }

        $this->load->model('residentmodel');
        $this->load->model('customermodel');
        $this->load->model('roomunionmodel');
        $this->load->model('storemodel');
        $resident   = Residentmodel::find($resident_id);
        $customer   = $resident->customer;
        $roomunion  = $resident->roomunion;
        $store      = $roomunion->store;
        $this->api_res(0,[
            'store'=>$store,
            'room'=>$roomunion,
            'customer'=>$customer,
            'resident'=>$resident,
            'amount'=>$amount,
            'orders'=>$orders
        ]);
    }


}
