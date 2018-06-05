<?php
/**
 * User: wws
 * Date: 2018-06-04
 * Time: 11:55
 * [web]查看账单 - 个人中心
 */
class Order extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('ordermodel');
    }

    /**
     *  查看账单列表
     */
    public function orderlist()
    {
        $this->load->model('storemodel');
        $this->load->model('roomunionmodel');
        //$uxid = intval(strip_tags(trim($post['uxid'])));
        $uxid = 7;
        $field = ['id','store_id', 'room_id'];
        if (isset($uxid)) {
            $contract = Ordermodel::with('storename')->with('roomnum')->where('uxid',$uxid)->get($field);
           // var_dump($contract);die();
            $this->api_res(0,[ 'list'=>$contract]);
        } else {
            $this->api_res(1005);
        }
    }

    /**
     *  查看账单
     */
    public function orderux()
    {
        $this->load->model('storemodel');
        $this->load->model('roomunionmodel');
        $this->load->model('residentmodel');
        $uxid = 7;
        $field = ['id','store_id','room_id','resident_id','type','paid'];
        if (isset($uxid)) {
            $list = Ordermodel::with('storename')->with('union')->with('residentder')->where('uxid',$uxid)->get($field);
            $this->api_res(0,[ 'list'=>$list]);
        } else {
            $this->api_res(1005);
        }
    }

    /**
     * 账单 状态列表
     */
    public function order()
    {
        $this->load->model('storemodel');
        $this->load->model('roomunionmodel');
        $this->load->model('residentmodel');
        $uxid = 7;
        $field = ['id','store_id','room_id','resident_id','type','paid','status'];
        if (isset($uxid)) {
            $list = Ordermodel::with('storename')->with('union')->with('residentder')->where('uxid',$uxid)->whereIn('status', [
                Ordermodel::STATE_PENDING,
                Ordermodel::STATE_CONFIRM,
                Ordermodel::STATE_COMPLETED,
            ])->get($field)->groupBy('status')->groupBy('store_id')->groupBy('room_id')->groupBy('resident_id');
            $this->api_res(0,[ 'status'=>$list]);
        } else {
            $this->api_res(1005);
        }
    }


}
