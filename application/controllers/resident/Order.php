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
     * 用户未支付订单列表
     */
    public function listUnpaidOrder()
    {
        $this->load->model('residentmodel');
        $this->load->model('ordermodel');
        $this->load->model('storemodel');
        $this->load->model('roomunionmodel');

        $resident   = Residentmodel::with(['roomunion','store','orders'=>function($query){
            $query->where('status',Ordermodel::STATE_PENDING);
        }])->where('customer_id',$this->user->id);
        $orders  = $resident->get()->map(function($query){
            $query->count   = count($query->orders);
            $query->amount  = ceil($query->orders->sum('money')*100)/100;
            $utility        = $this->utility($query->orders);
            return $query;
        })->where('amount','>',0);
        $arr=[];
        foreach ($orders as $order){
            $arr[]=$order;
        }
        $this->api_res(0,['residents'=>$arr]);
    }

    /**
     * 处理水电账单返回水电账单得详细信息
     */
    public function utility($order)
    {
        $this->load->model('meterreadingtransfermodel');
        foreach ($order as $key => $value){
            if ($value->transfer_id_s == 0||$value->transfer_id_e == 0){
                $value->this_reading    = '';
                $value->this_time       = '';
                $value->last_reading    = '';
                $value->last_time       = '';
            }else{
                $this_reading           = Meterreadingtransfermodel::where('id',$value->transfer_id_e)->first(['this_reading','this_time']);
                $last_reading           = Meterreadingtransfermodel::where('id',$value->transfer_id_s)->first(['this_reading','this_time']);
                $value->this_reading    = $this_reading->this_reading;
                $value->this_time       = date('Y-m-d',strtotime($this_reading->this_time));
                $value->last_reading    = $last_reading->this_reading;
                $value->last_time       = date('Y-m-d',strtotime($last_reading->this_time));
            }
        }
        return $order;

    }

    /**
     * 展示用户已经支付的订单列表
     */
    public function listPaidOrder()
    {
        $this->load->model('residentmodel');
        $this->load->model('ordermodel');
        $this->load->model('storemodel');
        $this->load->model('roomunionmodel');

        $resident   = Residentmodel::with(['roomunion','store','orders'=>function($query){
            $query->whereIn('status',[Ordermodel::STATE_CONFIRM,Ordermodel::STATE_COMPLETED]);
        }])->where('customer_id',$this->user->id);
        log_message('error','PAID-->'.$this->user->id);
        $orders  = $resident->get()->map(function($query){
            $query->count  = count($query->orders);
            $query->amount = number_format($query->orders->sum('money'),2,'.','');
            $utility        = $this->utility($query->orders);
            return $query;
        })->where('amount','>',0);

        $arr=[];
        foreach ($orders as $order){
            $arr[]=$order;
        }
        $this->api_res(0,['residents'=>$orders]);
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
        $orders = Ordermodel::where(['resident_id'=>$resident_id,'number'=>$number])->select(['id','number','money','status','type'])->get();
        if (0 == count($orders)) {
            $this->api_res(10017);
            return;
        }

        //计算总金额
        $amount = $orders->sum('money')*100;
        if (0 == $amount) {
            $this->api_res(10018);
            return;
        }

        //分类计算金额
        $order_class    = $orders->groupBy('type')
            ->map(function ($order){
                $order['sum']  = $order->sum('money')*100;
                return $order;
            });

        $this->load->model('residentmodel');
        $this->load->model('customermodel');
        $this->load->model('roomunionmodel');
        $this->load->model('storemodel');
        $resident   = Residentmodel::select(['id','name','phone','customer_id','room_id'])->find($resident_id);

        $roomunion  = $resident->roomunion()->select(['id','number','store_id','area'])->first();
        $store      = $roomunion->store()->select(['id','name'])->first();
        $this->api_res(0,[
            'store'=>$store,
            'room'=>$roomunion,
            'resident'=>$resident,
            'amount'=>$amount,
            'order_class'=>$order_class
        ]);
    }

    /**
     * 查看已支付订单详情
     */
    public function paid()
    {
        $this->load->model('residentmodel');
        $this->load->model('ordermodel');
        $this->load->model('roomunionmodel');
        $this->load->model('coupontypemodel');
        $this->load->model('couponmodel');
        $this->load->model('storemodel');

        $resident_id    = $this->input->post('resident_id',true);

        $resident   = Residentmodel::with(['roomunion','orders'=>function($query){
            $query->whereIn('status',[Ordermodel::STATE_CONFIRM,Ordermodel::STATE_COMPLETED])/*->orderBy('year','ASC')->orderBy('month','ASC')*/;
        }])
            ->where('customer_id',$this->user->id)
            ->findOrFail($resident_id);

        $room   = $resident->roomunion;
        $orders   = $resident->orders;

        if(!$room->store->pay_online){
            $this->api_res(10020);
            return;
        }
        if(count($orders) == 0){
            $this->api_res(0,['list'=>[]]);
            return;
        }
        //更新订单编号
//        $number = Ordermodel::newNumber($room->apartment->city->abbreviation, $room->apartment->abbreviation);
//        Newordermodel::whereIn('id', $neworders->pluck('id')->toArray())->update(['number' => $number]);

        $list   = $orders->groupBy('type')->map(function ($items, $type) {
            return [
                'name'   => Ordermodel::getTypeName($type),
                'amount' => number_format($items->sum('paid'), 2),
            ];
        });

        $totalMoney = number_format($orders->sum('money'), 2);

        $coupons    = $this->getCouponsAvailable($resident, $orders);

        $this->api_res(0,['orders'=>$orders,'list'=>$list,'coupons'=>$coupons,'resident'=>$resident,'room'=>$room,'totalMoeny'=>$totalMoney]);


    }

    /**
     * 获取用户需要支付的订单信息, 以及可以使用的优惠券信息
     */
    public function unpaid()
    {

        $this->load->model('residentmodel');
        $this->load->model('ordermodel');
        $this->load->model('roomunionmodel');
        $this->load->model('coupontypemodel');
        $this->load->model('couponmodel');
        $this->load->model('storemodel');

        $resident_id    = $this->input->post('resident_id',true);

        log_message('debug','test_resident'.$resident_id);

        $resident   = Residentmodel::with(['roomunion','orders'=>function($query){
            $query->where('status',Ordermodel::STATE_PENDING)/*->orderBy('year','ASC')->orderBy('month','ASC')*/;
        }])
            ->where('customer_id',$this->user->id)
            ->find($resident_id);
//            ->find(2745);

        if(!$resident){
            $this->api_res(1007);
            return;
        }

        $room   = $resident->roomunion;
        $orders   = $resident->orders;

        if(!$room->store->pay_online){
            $this->api_res(10020);
            return;
        }
        if(count($orders) == 0){
            $this->api_res(0,['list'=>[]]);
            return;
        }
        //更新订单编号
//        $number = Ordermodel::newNumber($room->apartment->city->abbreviation, $room->apartment->abbreviation);
//        Newordermodel::whereIn('id', $neworders->pluck('id')->toArray())->update(['number' => $number]);

        $list   = $orders->groupBy('type')->map(function ($items, $type) {
            return [
                'name'   => Ordermodel::getTypeName($type),
                'amount' => number_format($items->sum('paid'), 2)
            ];
        });

        $store  = $room->store;

        $totalMoney = number_format($orders->sum('money'), 2);

        $coupons    = $this->getCouponsAvailable($resident, $orders);

        $this->api_res(0,['orders'=>$orders,'list'=>$list,'store'=>$store,'coupons'=>$coupons,'resident'=>$resident,'room'=>$room,'totalMoeny'=>$totalMoney]);

    }

    /**
     * 查找订单可用的优惠券
     */
    private function getCouponsAvailable($resident, $orderCollection)
    {

        $orders = $orderCollection->groupBy('type');

        //优惠券的使用目前仅限于房租和代金券
        if (!isset($orders[Ordermodel::PAYTYPE_ROOM]) && !isset($orders[Ordermodel::PAYTYPE_MANAGEMENT])) {
            return null;
        }

        //月付用户首次支付不能使用优惠券
        if (1 == $resident->pay_frequency) {
            $tmpOrder = $orderCollection->first();
            if (Ordermodel::PAYSTATE_PAYMENT == $tmpOrder->pay_status AND $resident->begin_time->day < 21) {
                return null;
            }
        }

        //之前是查找住户的优惠券，这里改为查找用户customer下的优惠券
        $couopnCollection   = $resident->coupons()->where('status', Couponmodel::STATUS_UNUSED)->get();
//        $couopnCollection   = $this->user->coupons()->where('status', Couponmodel::STATUS_UNUSED)->get();
        $usageList          = $couopnCollection->groupBy('coupontype.limit');

        //找出房租可用的代金券
        $forRent    = $this
            ->getCouponByUsage(
                $resident,
                $orders,
                $usageList,
                Ordermodel::PAYTYPE_ROOM,
                $resident->real_rent_money
            );

        //找出物业服务费可用的代金券
        $forService = $this
            ->getCouponByUsage(
                $resident,
                $orders,
                $usageList,
                Ordermodel::PAYTYPE_MANAGEMENT,
                $resident->real_property_costs
            );

        $coupons = [];

        if ($forRent) {
            foreach ($forRent as $coupon) {
                $coupons[] = $coupon;
            }
        }

        if ($forService) {
            foreach ($forService as $coupon) {
                $coupons[] = $coupon;
            }
        }

        return isset($coupons)?$coupons:null;
    }

    /**
     * 根据优惠券的类型挑选优惠券
     */
    private function getCouponByUsage($resident, $orders, $usageList, $typeName, $price)
    {
        if (!isset($orders[$typeName]) OR !isset($usageList[$typeName])) {
            return false;
        }

        $couponNumber   = min(count($orders[$typeName]), $resident->pay_frequency);

        // $list   = $usageList[$typeName]->take($couponNumber);

        $list = $usageList[$typeName]->sortByDesc(function ($coupon) use ($typeName, $price) {
            return $this->calcDiscount($price, $coupon, $typeName);
        })->take($couponNumber);

        foreach ($list as $coupon) {
            $couponType = $coupon->coupontype;
            $coupons[] = [
                'id'        => $coupon->id,
                'type'      => $couponType->type,
                'usage'     => $typeName,
                'name'      => $couponType->name,
                'deadline'  => Carbon::parse($coupon->deadline)->toDateString(),
                'value'     => $couponType->discount,
                'discount'  => $this->calcDiscount($price, $coupon, $couponType),
            ];
        }
        return $coupons;
    }


    /**
     * 根据优惠券类型的不同, 计算出相应的价格
     */
    private function calcDiscount($price, $coupon, $couponType)
    {
        $couponType = $coupon->coupontype;

        switch ($couponType->type) {
            case Coupontypemodel::TYPE_CASH:
                $discount = $couponType->discount;
                break;
            case Coupontypemodel::TYPE_DISCOUNT:
                $discount = $price * (100 - $couponType->discount) / 100.0;
                break;
            case Coupontypemodel::TYPE_REMIT:
                $discount = $price;
                break;
            default:
                $discount = 0;
                break;
        }

        return $discount;
    }

}