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


    }

}
