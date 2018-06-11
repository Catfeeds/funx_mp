<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use EasyWeChat\Foundation\Application;
use EasyWeChat\Payment\Order;
use Carbon\Carbon;
use EasyWeChat\Message\Text;
use Illuminate\Database\Capsule\Manager as DB;
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/25 0025
 * Time:        18:20
 * Describe:    这里处理第一次租房 和 之后的续交 物业费 水电费
 */

class Payment extends MY_Controller
{

    protected $resident;

    /**
     * 构造方法
     */
    public function __contruct()
    {
        parent::__construct();

        $this->resident = NULL;
    }

    /**
     * 接收微信支付的配置请求
     * 获取微信支付的js配置
     */
//    public function config1()
//    {
//        //住户id
//        $residentId = trim($this->input->post('resident_id', true));
//        //订单编号
//        //$number     = trim($this->input->post('number', true));
//        //使用的优惠券
//        $couponIds  = $this->input->post('coupons[]', true)?$this->input->post('coupons[]', true):[];
//
//        $this->load->model('residentmodel');
//        $this->load->model('newordermodel');
//        $this->load->model('couponmodel');
//        $this->load->helper('wechat');
//
//        $this->resident = Residentmodel::with('neworders', 'coupons')->findOrFail($residentId);
//        //$this->checkUser($this->resident->uxid);
//
//        $orders         = $this->resident->neworders()->where('status', Newordermodel::STATE_PENDING)->get();
//
//        $coupons        = $this->resident->coupons()->whereIn('id', $couponIds)->get();
//
//        if (0 == count($orders)) {
//            $this->api_res(10017);
//            return;
//        }
//        //计算总金额
//        $amount = $orders->sum('money');
//
//        if (0 == $amount) {
//            $this->api_res(10018);
//            return;
//        }
//
//        try {
//            DB::beginTransaction();
//            //更新订单的付款方式和支付金额
//            $this->updatePayWayAndPaid($orders);
//
//            if (count($coupons)) {
//                $discount   = $this->amountOfDiscount($orders, $coupons);
//                $amount     = $amount - $discount;
//            }
//
//            $this->load->model('roomunionmodel');
//            $this->load->model('storemodel');
//            $this->load->model('roomtypemodel');
//            $this->load->helper('url');
//            $roomunion       = $this->resident->roomunion;
//            $store      = $roomunion->store;
//            $roomtype   = $roomunion->roomtype;
//            $attach     = ['resident_id' => $residentId];
//            $out_trade_no   = $residentId.'_'.mt_rand(10, 99);
//            $attributes = [
//                'trade_type'    => Ordermodel::PAYWAY_JSAPI,
//                'body'          => $store->name . '-' . $roomtype->name,
//                'detail'        => $store->name . '-' . $roomtype->name,
//                'out_trade_no'  => $out_trade_no,
//                'total_fee'     => $amount * 100,
//                'notify_url'    => site_url("pay/payment/notify/".$store->id),
//                'openid'        => $this->user->openid,
//                'attach'        => serialize($attach),
//            ];
//
//            $this->load->model('storepaymodel');
//            $store_pay  = new Storepaymodel();
//            $store_pay->out_trade_no    = $out_trade_no;
//            $store_pay->store_id    = $store->id;
//            $store_pay->amount  = $amount;
//            $store_pay->data['orders']    = $orders->toArray();
//            $store_pay->data['coupons']    = $coupons->toArray();
//            $store_pay->save();
//
//            $wechatConfig   = getCustomerWechatConfig();
////            $wechatConfig['payment']['merchant_id'] = $store->payment_merchant_id;
////            $wechatConfig['payment']['key']         = $store->payment_key;
//
//            $app            = new Application($wechatConfig);
//            $wechatOrder    = new Order($attributes);
//            $payment        = $app->payment;
//            $result         = $payment->prepare($wechatOrder);
//
//            if (!($result->return_code == 'SUCCESS' && $result->result_code == 'SUCCESS')) {
//                throw new Exception($result->return_msg);
//            }
//            //生成js配置
//            $json = $payment->configForPayment($result->prepay_id, false);
//            DB::commit();
//        } catch (Exception $e) {
//            DB::rollBack();
//            log_message('error', $e->getMessage());
//            throw $e;
//        }
//
//        $this->api_res(0,['json'=>$json]);
//    }

    /**
     * 接收微信支付的配置请求
     * 获取微信支付的js配置
     */
    public function config()
    {

        //住户id
        $residentId = trim($this->input->post('resident_id', true));
//        $residentId = 2640;
        //使用的优惠券
        $couponIds  = $this->input->post('coupons[]', true)?$this->input->post('coupons[]', true):[];

        $this->load->model('residentmodel');
        $this->load->model('ordermodel');
        $this->load->model('couponmodel');
        $this->load->helper('wechat');

        $this->resident = Residentmodel::with('orders', 'coupons')->findOrFail($residentId);
        //$this->checkUser($this->resident->uxid);

        $orders         = $this->resident->orders()->where('status', Ordermodel::STATE_PENDING)->get();
        //$orders         = Ordermodel::get();

        $coupons        = $this->resident->coupons()->whereIn('id', $couponIds)->get();

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

        try {
            DB::beginTransaction();
            //更新订单的付款方式和支付金额
            $this->updatePayWayAndPaid($orders);
            $discount   = 0;
            if (count($coupons)) {
                $discount   = $this->amountOfDiscount($orders, $coupons);
                $amount     = $amount - $discount;
            }

            $this->load->model('roomunionmodel');
            $this->load->model('storemodel');
            $this->load->model('roomtypemodel');
            $this->load->helper('url');
            $roomunion       = $this->resident->roomunion;
            $store      = $roomunion->store;
            $roomtype   = $roomunion->roomtype;
            $attach     = ['resident_id' => $residentId];
            $out_trade_no   = $residentId.'_'.date('YmdHis',time()).mt_rand(10, 99);
            $attributes = [
                'trade_type'    => Ordermodel::PAYWAY_JSAPI,
                'body'          => $store->name . '-' . $roomtype->name,
                'detail'        => $store->name . '-' . $roomtype->name,
                'out_trade_no'  => $out_trade_no,
                'total_fee'     => $amount * 100,
//                'total_fee'     => 1,
//                'notify_url'    => site_url("pay/payment/notify/".$store->id),
                'notify_url'    => "http://tapi.web.funxdata.com/pay/payment/notify/".$store->id,
                'openid'        => $this->user->openid,
//                'openid'        => 'ob4npwr_tU8D-XHmgXPMxEqcrj6c',
                'attach'        => serialize($attach),
            ];
            $this->load->model('storepaymodel');
            $store_pay  = new Storepaymodel();
            $store_pay->out_trade_no    = $out_trade_no;
            $store_pay->store_id    = $store->id;
            $store_pay->amount  = $amount;
            $store_pay->discount  = $discount;
            $store_pay->status  = 'UNDONE';
            $store_pay->resident_id  = $residentId;
            $store_pay->start_date  = date('Y-m-d H-i-s',time());
            $store_pay->data=['orders'=>$orders,'coupons'=>$coupons];
            $store_pay->save();

            $orders->each(function ($query) use($out_trade_no,$store_pay){
                $query->out_trade_no = $out_trade_no;
                $query->store_pay_id = $store_pay->id;
                $query->save();
            });


            $wechatConfig   = getCustomerWechatConfig();
//            $wechatConfig['payment']['merchant_id'] = $store->payment_merchant_id;
//            $wechatConfig['payment']['key']         = $store->payment_key;

            $app            = new Application($wechatConfig);
            $wechatOrder    = new Order($attributes);
            $payment        = $app->payment;
            $result         = $payment->prepare($wechatOrder);
            if (!($result->return_code == 'SUCCESS' && $result->result_code == 'SUCCESS')) {
                throw new Exception($result->return_msg);
            }
//            //生成js配置
            $all_result['json'] = $payment->configForPayment($result->prepay_id, false);
//            log_message('error',$json);
            DB::commit();
        } catch (Exception $e) {

            DB::rollBack();
            log_message('error', $e->getMessage());
            throw $e;
        }
        $this->api_res(0,$all_result);
    }



    /**
     * 使用优惠券支付时计算金额
     */
    private function amountOfDiscount($orderCollection, $coupons)
    {
        $coupons    = $coupons->groupBy('coupon_type.limit');
        $orders     = $orderCollection->groupBy('type');

        $discount   = 0;
        $rentOrders = $orders->pull(Ordermodel::PAYTYPE_ROOM);

        if (count($rentOrders)) {
            $discount += $this->calcDiscountByType(
                $rentOrders,
                $coupons,
                Ordermodel::PAYTYPE_ROOM,
                $this->resident->real_rent_money
            );
        }

        $managementOrders = $orders->pull(Ordermodel::PAYTYPE_MANAGEMENT);

        if (count($managementOrders)) {
            $discount += $this->calcDiscountByType(
                $managementOrders,
                $coupons,
                Ordermodel::PAYTYPE_MANAGEMENT,
                $this->resident->real_property_costs
            );
        }

        return $discount;
    }

    /**
     * 更新订单的付款方式和支付金额
     */
    private function updatePayWayAndPaid($orderCollection)
    {
        $orderCollection->each(function ($order) {
            $order->update([
                'pay_type'  => Ordermodel::PAYWAY_JSAPI,
                'paid'      => $order->money,
            ]);
        });

        return true;
    }

    /**
     * 计算通过使用优惠券可以获得的优惠
     * 每次账单中, 房租和物业管理费的订单均最多只有一笔能享受优惠
     * 原因是, 理论上, 每笔账单中, 一般只有一笔房租和一笔物业服务费账单
     * 如果多于一笔, 则可能是上一笔的欠费, 还有一种可能是, 初次缴费和次月缴费一起交了.
     * 如果多于一笔, 则选金额较大的那一笔来参与优惠的计算
     */
    private function calcDiscountByType($orderCollection, $coupons, $typeName, $price)
    {
        if (!isset($coupons[$typeName])) {
            return 0;
        }

        $orderCollection = $orderCollection->sortByDesc('money');
        $order = $orderCollection->first();

        if (1 == $order->resident->pay_frequency && Ordermodel::PAYSTATE_PAYMENT == $order->pay_status && count($orderCollection) == 1) {
            return 0;
        }

        $discount = 0;

        //遍历优惠券列表, 计算优惠金额
        foreach ($coupons[$typeName] as $key => $item) {
            $couponType = $item->coupon_type;

            switch ($couponType->type) {
                case Coupontypemodel::TYPE_CASH:
                    $deduction = min($order->money, $couponType->discount);
                    break;
                case Coupontypemodel::TYPE_DISCOUNT:
                    $deduction = min($order->money, $price * (100 - $couponType->discount) / 100.0);
                    break;
                case Coupontypemodel::TYPE_REMIT:
                    $deduction = min($order->money, $price);
                    break;
                default:
                    $deduction = 0;
                    break;
            }

            $discount += $deduction;

            $item->update([
                'order_id'  => $order->id,
                'status'    => Couponmodel::STATUS_OCCUPIED,
            ]);

            $order->update(['paid' => max(0, $order->money - $deduction)]);
        }

        return $discount;
    }

    /**
     * 微信的回调
     * 这里用户是使用微信进行支付,
     * 可以判断用户是否支付成功, 是否还有必要让员工进行确认呢
     */
//    public function notify()
//    {
//
//        $store_id    = $this->uri->segment(4);
//
//        $this->load->model('storemodel');
//        $store      = Storemodel::findOrFail($store_id);
//
//        $this->load->helper('wechat');
//        $customerWechatConfig   = getCustomerWechatConfig();
////      $customerWechatConfig['payment']['merchant_id'] = $store->payment_merchant_id;
////      $customerWechatConfig['payment']['key']         = $store->payment_key;
//
//        $app    = new Application($customerWechatConfig);
//
//        $response   = $app->payment->handleNotify(function($notify, $successful) use ($app) {
//            try {
//                DB::beginTransaction();
//
//                $data       = explode('_', $notify->out_trade_no);
//                $number     = $data[0];
//                $attach     = unserialize($notify->attach);
//                $this->load->model('residentmodel');
//                $resident   = Residentmodel::with('neworders')->find($attach['resident_id']);
//
//                log_message('error', 'notify-arrived' . $number);
//
//                if (!count($resident)) {
//                    return true;
//                }
//
//                if(!$successful){
//                    return true;
//                }
//                $this->load->model('newordermodel');
//                $orders     = $resident->neworders()->where('status', Newordermodel::STATE_PENDING)->get();
//
//                if (!count($orders)) {
//                    return true;
//                }
//
//                foreach ($orders as $order) {
//                    $orderIds[]    = $order->id;
//                    $order->status = Newordermodel::STATE_CONFIRM;
//                    $order->save();
//
//                    if ($order->type == 'DEIVCE') {
//                        $this->load->model('devicemodel');
//                        $temp = Devicemodel::find($order->other_id);
//                        if (!empty($temp)) {
//                            $temp->status = Devicemodel::STATE_CONFIRM;
//                            $temp->save();
//                        }
//                    }
//
//                    if ($order->type == 'UTILITY') {
//                        $this->load->model('utilitymodel');
//                        $temp = Utilitymodel::find($order->other_id);
//                        if (!empty($temp)) {
//                            $temp->status = Utilitymodel::STATE_CONFIRM;
//                            $temp->save();
//                        }
//                    }
//                }
//                $this->load->model('couponmodel');
//                Couponmodel::whereIn('order_id', $orderIds)->update(['status' => Couponmodel::STATUS_USED]);
//
//                try {
//
//
//                    //发送模板消息
////                    $this->sendTemplateMessages($resident, $number, Ordermodel::PAYWAY_JSAPI, $notify->total_fee / 100);
//                    log_message('info','微信回调成功发送模板消息');
//                } catch (Exception $e) {
//                    log_message('error', '微信支付-模板消息通知失败：' . $e->getMessage());
//                    throw $e;
//                }
//                DB::commit();
//            } catch (Exception $e) {
//                DB::rollBack();
//                log_message('error', $e->getMessage());
//                throw $e;
//                // return false;
//            }
//
//            return true;
//        });
//
//        $response->send();
//    }

    /**
     * 微信的回调
     * 这里用户是使用微信进行支付,
     * 可以判断用户是否支付成功, 是否还有必要让员工进行确认呢
     */
    public function notify()
    {
        log_message('error','AA');

        $store_id    = $this->uri->segment(4);

        $this->load->model('storemodel');
        $store      = Storemodel::findOrFail($store_id);

        $this->load->helper('wechat');
        $customerWechatConfig   = getCustomerWechatConfig();
//      $customerWechatConfig['payment']['merchant_id'] = $store->payment_merchant_id;
//      $customerWechatConfig['payment']['key']         = $store->payment_key;

        $app    = new Application($customerWechatConfig);

        $response   = $app->payment->handleNotify(function($notify, $successful) use ($app) {
            try {
                log_message('error','---->1');
                DB::beginTransaction();

                $data       = explode('_', $notify->out_trade_no);
                //$residentId     = $data[0];
                $attach     = unserialize($notify->attach);
                $this->load->model('residentmodel');
                $this->load->model('ordermodel');
                $resident   = Residentmodel::with('orders')->find($attach['resident_id']);

                log_message('error', 'notify-arrived--->' . $notify->out_trade_no);

                if (empty($resident)) {
                    return true;
                }

                if(!$successful){
                    return true;
                }

                $orders     = $resident->orders()->where('status', Ordermodel::STATE_PENDING)->where('out_trade_no',$notify->out_trade_no)->get();

                if (!count($orders)) {
                    return true;
                }

                log_message('error','---->2');
                $pay_date   = date('Y-m-d H:i:s',time());

                foreach ($orders as $order) {
                    $orderIds[]    = $order->id;
                    $order->pay_date    = $pay_date;
                    $order->status = Ordermodel::STATE_CONFIRM;
                    $order->out_trade_no = $notify->out_trade_no;
                    //$order->out_trade_no = $notify->out_trade_no;
                    $order->save();

                    if ($order->type == 'DEIVCE') {
                        $this->load->model('devicemodel');
                        $temp = Devicemodel::find($order->other_id);
                        if (!empty($temp)) {
                            $temp->status = Devicemodel::STATE_CONFIRM;
                            $temp->save();
                        }
                    }

                    if ($order->type == 'UTILITY') {
                        $this->load->model('utilitymodel');
                        $temp = Utilitymodel::find($order->other_id);
                        if (!empty($temp)) {
                            $temp->status = Utilitymodel::STATE_CONFIRM;
                            $temp->save();
                        }
                    }
                }

                log_message('error','---->3');
                $this->load->model('couponmodel');
                Couponmodel::whereIn('order_id', $orderIds)->update(['status' => Couponmodel::STATUS_USED]);

                $this->load->model('storepaymodel');
                $store_pay  = Storepaymodel::where('resident_id',$resident->id)->where('out_trade_no',$notify->out_trade_no)->first();
                //test
                if(!empty($store_pay))
                {
                    $store_pay->notify_date = $pay_date;
                    $store_pay  ->status    = 'DONE';
                    $store_pay->save();
                }
                log_message('error','---->333');

                DB::commit();
                try {


                    //发送模板消息
//                    $this->sendTemplateMessages($resident, $number, Ordermodel::PAYWAY_JSAPI, $notify->total_fee / 100);
                    log_message('info','微信回调成功发送模板消息');
                } catch (Exception $e) {
                    log_message('error', '微信支付-模板消息通知失败：' . $e->getMessage());
                    throw $e;
                }

            } catch (Exception $e) {
                DB::rollBack();
                log_message('error', $e->getMessage());
                throw $e;
               // return false;
            }

            return true;
        });

        $response->send();
    }

    /**
     * 发送模板消息
     */
    private function sendTemplateMessages($resident, $number, $payWay, $totalMoney)
    {
        $room       = $resident->room;
        $app        = new Application(getCustomerWechatConfig());
//        $eApp       = new Application(getEmployeeWechatConfig());
        $payType    = ($payWay == Ordermodel::PAYWAY_BANK) ? "银行卡" : "微信";
        $payInfo    = ($payWay == Ordermodel::PAYWAY_BANK) ? "提单提交成功,请到前台刷卡!" : "微信支付成功!";

        $data = [
            "first"             => $payInfo,
            "orderMoneySum"     => $totalMoney.'元',
            "orderProductName"  => $room->apartment->name.'-'.$room->roomtype->name.'-'.$room->number,
            "Remark"            => "请等待工作人员审核!",
        ];
        $app->notice->uses(TMPLMSG_CUSTOMER_PAYMENT)
            ->withUrl(site_url(['order', 'detail', $resident->id]))
            ->andData($data)
            ->andReceiver($resident->customer->openid)
            ->send();

//        if ($resident->employee) {
//            $eData  = [
//                'first'     => "{$room->resident->name}通过-{$payType}-支付订单成功!",
//                'keyword1'  => "{$room->apartment->name}-{$room->roomtype->name}-{$room->number}",
//                'keyword2'  => $number,
//                'keyword3'  => date('Y-m-d H:i:s'),
//                'keyword4'  => "用户支付成功!",
//                'remark'    => '请尽快确认用户支付!',
//            ];
//            $eApp->notice->uses(TMPLMSG_EMPLOYEE_CHECK)
//                ->withUrl(employee_url('order/detaillist/'.$number))
//                ->andData($eData)
//                ->andReceiver($resident->employee->openid)
//                ->send();
//        }
    }

    /**
     * 计算支付的金额
     */
    private function calcPaymentMoney($resident, $payType)
    {
        if ($payType == Ordermodel::PAYSTATE_RENEWALS) {
            $rentsMoney     = ($resident->real_rent_money + $resident->real_property_costs) * count($this->rents);
            $utilitiesMoney = Utilitymodel::whereIn('id', $this->utilities)->sum('money');
            $devicesMoney   = Devicemodel::whereIn('id', $this->devices)->sum('money');

            return sprintf("%.2f", $rentsMoney + $utilitiesMoney + $devicesMoney - $resident->discount_money);
        } else {
            $totalMoney = $resident->first_pay_money + $resident->deposit_money;
            $realMoney  = $totalMoney - $resident->discount_money - $resident->book_money;

            return sprintf("%.2f", $realMoney);
        }

        return 0;
    }
}
