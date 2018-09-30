<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/6/9 0009
 * Time:        9:40
 * Describe:
 */
class Billmodel extends Basemodel
{
    protected $table    = 'boss_bill';

    protected $casts    = ['data'=>'array'];

    public function roomunion()
    {

        return $this->belongsTo(Roomunionmodel::class,'room_id');
    }

    public function store()
    {

        return $this->belongsTo(Storemodel::class,'store_id');
    }

    public function resident()
    {

        return $this->belongsTo(Residentmodel::class,'resident_id');
    }

    public function employee()
    {

        return $this->belongsTo(Employeemodel::class,'employee_id');
    }

    /**
     * 检索当日确定的账单的数量
     */
    public static function ordersConfirmedToday()
    {
        return Billmodel::whereDate('updated_at', '=', date('Y-m-d'))
            ->count();
    }

    /**
     * 生成流水
     */
    public static function  createBill($data){

        $count      = self::ordersConfirmedToday() + 1;
        $dateString = date('Ymd');
        $bill   = new Billmodel();
        $bill->company_id   = $data['company_id'];
        $bill->sequence_number = sprintf("%s%06d", $dateString, $count);
        $bill->store_id = $data['store_id'];
        $bill->employee_id  = $data['employee_id'];
        $bill->resident_id  = $data['resident_id'];
        $bill->customer_id  = $data['customer_id'];
        $bill->uxid         = $data['uxid'];
        $bill->room_id      = $data['room_id'];
        $bill->money        = $data['money'];
        $bill->type         = $data['type'];
        $bill->pay_type     = $data['pay_type'];
        $bill->pay_date     = $data['pay_date'];
        $bill->pre_money    = $data['pre_money'];
        $bill->save();
        return $bill;
    }

//
    private function createBill1($orders, $payWay = null) {
        $this->load->model('billmodel');
        $bill       = new Billmodel();
        $bill->id   = '';
        $count      = $this->billmodel->ordersConfirmedToday() + 1;
        $dateString = date('Ymd');
        $this->load->model('residentmodel');

        $bill->sequence_number = sprintf("%s%06d", $dateString, $count);
        $bill->store_id        = $orders[0]->store_id;
//        $bill->employee_id         =    $orders[0]->employee_id;
        $bill->employee_id = $this->employee->id;
        $bill->resident_id = $orders[0]->resident_id;
        $bill->customer_id = $orders[0]->customer_id;
        $bill->uxid        = $orders[0]->uxid;
        $bill->room_id     = $orders[0]->room_id;
        $orderIds          = array();

        $change_resident = false;
        foreach ($orders as $order) {

            $orderIds[]  = $order->id;
            $bill->money = $bill->money + $order->paid;
//            if($order->pay_type=='REFUND'){
            //                $bill->type                =    'OUTPUT';
            //            }else{
            //                $bill->type                =    'INPUT';
            //            }
            if ($order->pay_type == 'ROOM') {
                $change_resident = true;
            }
        }
        if ($change_resident) {
            $Resident      = Residentmodel::find($orders[0]->resident_id);
            $Resident_time = substr($Resident['begin_time'], 0, 7);
            if ($Resident_time == substr($orders[0]->pay_type, 0, 7)) {
                Residentmodel::where('id', $orders[0]->resident_id)->update(['status' => 'NORMAL']);
            }
        }

        $bill->pay_type     = $payWay ? $payWay : $orders[0]->pay_type;
        $bill->confirm      = '';
        $bill->pay_date     = date('Y-m-d H:i:s', time());
        $bill->data         = '';
        $bill->confirm_date = date('Y-m-d H:i:s', time());

        //如果是微信支付
        $bill->out_trade_no = '';
        $bill->store_pay_id = '';

        $res = $bill->save();
        if (isset($res)) {
            Ordermodel::whereIn('id', $orderIds)->update(['sequence_number' => $bill->sequence_number]);
        }
        return $res;
    }
}
