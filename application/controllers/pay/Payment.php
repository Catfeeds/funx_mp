<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use EasyWeChat\Foundation\Application;
use EasyWeChat\Payment\Order;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/25 0025
 * Time:        18:20
 * Describe:    这里处理第一次租房 和 之后的续交 物业费 水电费
 */

class Payment extends MY_Controller {

    protected $resident;

    /**
     * 构造方法
     */
    public function __contruct() {
        parent::__construct();

        $this->resident = NULL;
    }

    /**
     * 接收微信支付的配置请求
     * 获取微信支付的js配置
     */
    public function config() {

        //住户id
        $residentId = trim($this->input->post('resident_id', true));

        //使用的优惠券
        $couponIds = $this->input->post('coupons[]', true) ? $this->input->post('coupons[]', true) : [];

        $this->load->model('residentmodel');
        $this->load->model('ordermodel');
        $this->load->model('couponmodel');
        $this->load->model('roomunionmodel');
        $this->load->helper('wechat');
        $this->load->model('storemodel');
        $this->load->model('roomtypemodel');
        $this->load->model('coupontypemodel');

        $this->resident = Residentmodel::with('orders', 'coupons')->findOrFail($residentId);

        if (!$this->resident->roomunion->store->pay_online) {
            $this->api_res(10020);
            return;
        }

        $orders = $this->resident->orders()->where('status', Ordermodel::STATE_PENDING)->get();

        //之前是查找住户的优惠券，这里改为查找用户的优惠券
        $coupons = $this->resident->coupons()->whereIn('id', $couponIds)->get();

        if (0 == count($orders)) {
            $this->api_res(10017);
            return;
        }
        $store_ids = [];
        foreach ($coupons as $value) {
            if ($value->store_ids) {
                $arr = explode(',', $value->store_ids);
                for ($i = 0; $i < (count($arr)); $i++) {
                    $store_ids[] = $arr[$i];
                }
            }
        }
        $store_id = $this->resident->store_id;

        //计算总金额
        $amount = $orders->sum('money');
        log_message('debug', 'amount.sum:' . $amount);
        if (0 == $amount) {
            $this->api_res(10018);
            return;
        }

        try {
            DB::beginTransaction();
            //更新订单的付款方式和支付金额
            $this->updatePayWayAndPaid($orders);
            $discount = 0;
            if (count($coupons) && ((in_array($store_id, $store_ids)) || count($store_ids) == 0)) {
                $discount = $this->amountOfDiscount($orders, $coupons);
                $amount   = $amount - $discount;
            }
            log_message('debug', 'discount:' . $discount);
            $this->load->helper('url');
            $roomunion    = $this->resident->roomunion;
            $store        = $roomunion->store;
            $roomtype     = $roomunion->roomtype;
            $attach       = ['resident_id' => $residentId];
            $out_trade_no = $store_id . '_' . $residentId . '_' . date('YmdHis', time()) . mt_rand(10, 99);
            if (ENVIRONMENT == 'development') {
                $attributes = [
                    'trade_type'   => Ordermodel::PAYWAY_JSAPI,
                    'body'         => $store->name . '-' . $roomtype->name,
                    'detail'       => $store->name . '-' . $roomtype->name,
                    'out_trade_no' => $out_trade_no,
                    'total_fee'    => 1,
                    'notify_url'   => config_item('base_url') . "pay/payment/notify/" . $store->id,
                    'openid'       => $this->user->openid,
                    'attach'       => serialize($attach),
                ];
            } else {
                $attributes = [
                    'trade_type'   => Ordermodel::PAYWAY_JSAPI,
                    'body'         => $store->name . '-' . $roomtype->name,
                    'detail'       => $store->name . '-' . $roomtype->name,
                    'out_trade_no' => $out_trade_no,
                    'total_fee'    => $amount * 100,
                    'notify_url'   => config_item('base_url') . "pay/payment/notify/" . $store->id,
                    'openid'       => $this->user->openid,
                    'attach'       => serialize($attach),
                ];
            }

            $this->load->model('storepaymodel');
            $store_pay               = new Storepaymodel();
            $store_pay->out_trade_no = $out_trade_no;
            $store_pay->store_id     = $store->id;
            $store_pay->amount       = $amount;
            $store_pay->discount     = $discount;
            $store_pay->status       = 'UNDONE';
            $store_pay->resident_id  = $residentId;
            $store_pay->start_date   = date('Y-m-d H-i-s', time());
            $store_pay->data         = ['orders' => $orders, 'coupons' => $coupons];
            $store_pay->save();

            $orders->each(function ($query) use ($out_trade_no, $store_pay) {
                $query->out_trade_no = $out_trade_no;
                $query->store_pay_id = $store_pay->id;
                $query->save();
            });

            $wechatConfig = getCustomerWechatConfig();

            //微信支付商户id
            if (ENVIRONMENT != 'development') {
                $wechatConfig['payment']['merchant_id'] = $store->payment_merchant_id;
                $wechatConfig['payment']['key']         = $store->payment_key;
            }
            $app         = new Application($wechatConfig);
            $wechatOrder = new Order($attributes);
            $payment     = $app->payment;
            $result      = $payment->prepare($wechatOrder);
            if (!($result->return_code == 'SUCCESS' && $result->result_code == 'SUCCESS')) {
                throw new Exception($result->return_msg);
            }

            // 生成js配置
            $all_result['json'] = $payment->configForPayment($result->prepay_id, false);
            log_message('debug', 'discount.amount:' . $amount);
            DB::commit();
        } catch (Exception $e) {

            DB::rollBack();
            log_message('error', $e->getMessage());
            throw $e;
        }
        $this->api_res(0, $all_result);
    }

    /**
     * 使用优惠券支付时计算金额
     */
    private function amountOfDiscount($orderCollection, $coupons) {
        $coupons = $coupons->groupBy('coupontype.limit');
        $orders  = $orderCollection->groupBy('type');

        $discount   = 0;
        $rentOrders = $orders->pull(Ordermodel::PAYTYPE_ROOM);

        if (count($rentOrders)) {
            $discount += $this->calcDiscountByType(
                $rentOrders,
                $coupons,
                Ordermodel::PAYTYPE_ROOM,
                $this->resident->real_rent_money
            );
            log_message('debug', 'ROOM.discount:' . $discount);
        }

        $managementOrders = $orders->pull(Ordermodel::PAYTYPE_MANAGEMENT);

        if (count($managementOrders)) {
            $discount += $this->calcDiscountByType(
                $managementOrders,
                $coupons,
                Ordermodel::PAYTYPE_MANAGEMENT,
                $this->resident->real_property_costs
            );
            log_message('debug', 'MANAGEMENT.discount:' . $discount);
        }

        return $discount;
    }

    /**
     * 更新订单的付款方式和支付金额
     */
    private function updatePayWayAndPaid($orderCollection) {
        $orderCollection->each(function ($order) {
            $order->update([
                'pay_type' => Ordermodel::PAYWAY_JSAPI,
                'paid'     => $order->money,
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
    private function calcDiscountByType($orderCollection, $coupons, $typeName, $price) {
        if (!isset($coupons[$typeName])) {
            return 0;
        }

        $orderCollection = $orderCollection->sortByDesc('money');
        $order           = $orderCollection->first();

        if (1 == $order->resident->pay_frequency && Ordermodel::PAYSTATE_PAYMENT == $order->pay_status && count($orderCollection) == 1) {
            return 0;
        }

        $discount = 0;

        //遍历优惠券列表, 计算优惠金额
        foreach ($coupons[$typeName] as $key => $item) {
            $couponType = $item->coupontype;

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
            log_message('debug', 'deduction:' . $deduction);
            $discount += $deduction;

            $item->update([
                'order_id' => $order->id,
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
    public function notify() {
        $store_id = $this->uri->segment(4);

        $this->load->model('storemodel');
        $store = Storemodel::findOrFail($store_id);

        $this->load->helper('wechat');

        $customerWechatConfig = getCustomerWechatConfig();
        if (ENVIRONMENT != 'development') {
            $customerWechatConfig['payment']['merchant_id'] = $store->payment_merchant_id;
            $customerWechatConfig['payment']['key']         = $store->payment_key;
        }
        $app = new Application($customerWechatConfig);

        $response = $app->payment->handleNotify(function ($notify, $successful) use ($app) {
            try {
                DB::beginTransaction();

                $data = explode('_', $notify->out_trade_no);
                //$residentId     = $data[0];
                $attach = unserialize($notify->attach);
                $this->load->model('residentmodel');
                $this->load->model('ordermodel');
                $resident = Residentmodel::with('orders')->find($attach['resident_id']);

                log_message('debug', 'notify-arrived--->' . $notify->out_trade_no);

                if (empty($resident)) {
                    return true;
                }

                if (!$successful) {
                    return true;
                }

                $orders = $resident->orders()->where('status', Ordermodel::STATE_PENDING)->where('out_trade_no', $notify->out_trade_no)->get();

                if (!count($orders)) {
                    return true;
                }
                $pay_date = date('Y-m-d H:i:s', time());

                foreach ($orders as $order) {
                    $orderIds[]          = $order->id;
                    $order->pay_date     = $pay_date;
                    $order->status       = Ordermodel::STATE_CONFIRM;
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

                }

                $this->load->model('couponmodel');
                Couponmodel::whereIn('order_id', $orderIds)->update(['status' => Couponmodel::STATUS_USED]);

                $this->load->model('storepaymodel');
                $store_pay = Storepaymodel::where('resident_id', $resident->id)->where('out_trade_no', $notify->out_trade_no)->first();
                //test
                if (!empty($store_pay)) {
                    $store_pay->notify_date = $pay_date;
                    $store_pay->status      = 'DONE';
                    $store_pay->save();
                }

                DB::commit();
                try {
                    log_message('info', '微信回调成功发送模板消息');
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
    private function sendTemplateMessages($resident, $number, $payWay, $totalMoney) {
        $room = $resident->room;
        $app  = new Application(getCustomerWechatConfig());

        $payType = ($payWay == Ordermodel::PAYWAY_BANK) ? "银行卡" : "微信";
        $payInfo = ($payWay == Ordermodel::PAYWAY_BANK) ? "提单提交成功,请到前台刷卡!" : "微信支付成功!";

        $data = [
            "first"            => $payInfo,
            "orderMoneySum"    => $totalMoney . '元',
            "orderProductName" => $room->apartment->name . '-' . $room->roomtype->name . '-' . $room->number,
            "Remark"           => "请等待工作人员审核!",
        ];
        $app->notice->uses(TMPLMSG_CUSTOMER_PAYMENT)
            ->withUrl(site_url(['order', 'detail', $resident->id]))
            ->andData($data)
            ->andReceiver($resident->customer->openid)
            ->send();

    }

    /**
     * 计算支付的金额
     */
    private function calcPaymentMoney($resident, $payType) {
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

    /**
     *
     * 创建生成流水账单
     * 根据流水账单来记录用户的每次支付记录
     *
     */

    private function createBill($orders) {

        $this->load->model('billmodel');
        $bill       = new Billmodel();
        $bill->id   = '';
        $count      = $this->billmodel->ordersConfirmedToday() + 1;
        $dateString = date('Ymd');
        $this->load->model('residentmodel');

        $bill->sequence_number = sprintf("%s%06d", $dateString, $count);

        $bill->store_id    = $orders[0]->store_id;
        $bill->employee_id = $orders[0]->employee_id;
        $bill->resident_id = $orders[0]->resident_id;
        $bill->customer_id = $orders[0]->customer_id;
        $bill->uxid        = $orders[0]->uxid;
        $bill->room_id     = $orders[0]->room_id;
        $orderIds          = array();

        $change_resident = false;
        foreach ($orders as $order) {

            $orderIds[]  = $order->id;
            $bill->money = $bill->money + $order->paid;
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

        $bill->pay_type     = $orders[0]->pay_type;
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
