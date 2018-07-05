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
        $uxid = CURRENT_ID;
        $field = ['id','store_id', 'room_id'];
        if (isset($uxid)) {
            $contract = Ordermodel::with('storename')->with('roomnum')->where('uxid',$uxid)->get($field);
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
        $uxid = CURRENT_ID;
        $resident   = Residentmodel::where('customer_id',$uxid)->first();
        $paid    = $resident->with('store')->orders()
            ->whereIn('status',[Ordermodel::STATE_CONFIRM,Ordermodel::STATE_COMPLETED])
            ->orderBy('year','DESC')
            ->orderBy('month','DESC')
            ->get()
            ->map(function($order){
                $order->date    = $order->year.'-'.$order->month;
                return $order;
            });
        $paid_money     = $paid->sum('money');
        $paid   = $paid->groupBy('date')->map(function($paid){
            $a  = [];
            $a['orders']    = $paid->toArray();
            $a['total_money']=$paid->sum('money');
            $a['total_paid']=$paid->sum('paid');
            $a['discount_money']=$paid->sum('discount');
            return $a;
        });
        $this->api_res(0,compact('paid_money','paid'));
    }

    /**
     * 账单 状态列表
     */
    public function order()
    {
        $this->load->model('storemodel');
        $this->load->model('roomunionmodel');
        $this->load->model('residentmodel');
        $uxid = CURRENT_ID;
        $field = ['id', 'store_id', 'room_id', 'name', 'id'];
        if (isset($uxid)) {
            $resident_ids = Ordermodel::whereIn('status', [
                Ordermodel::STATE_PENDING,
                Ordermodel::STATE_CONFIRM,
                Ordermodel::STATE_COMPLETED,])->groupBy('resident_id')->get(['resident_id'])->map(function ($id) {
                return $id->resident_id;
            });
            $list = Residentmodel::with('orders', 'roomunion1', 'store')->whereIn('id', $resident_ids->toArray())->where('uxid', $uxid)->get($field)
                ->map(function ($query){
                    $query->sum = $query->orders->sum('money');
                    return $query;
                })->toArray();

            $this->api_res(0, ['list' => $list]);
        } else {
            $this->api_res(1005);
        }
    }

}
