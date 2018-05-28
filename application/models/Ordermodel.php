<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Carbon\Carbon;
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/14 0014
 * Time:        15:07
 * Describe:
 */
class Ordermodel extends Basemodel{

    /**
     * 订单状态的常量
     */
    const STATE_GENERATED   = 'GENERATE';   // 后台生成账单的状态, 未发送给用户
    const STATE_AUDITED     = 'AUDITED';    // 后台生成账单的状态, 未发送给用户
    const STATE_PENDING     = 'PENDING';    // 下单之后的默认状态,等待付款
    const STATE_CONFIRM     = 'CONFIRM';    // 付完款 等待确认
    const STATE_COMPLATE    = 'COMPLATE';   // 完成
    const STATE_COMPLETED   = 'COMPLATE';   // 完成, 我不喜欢上面的错别字, 因此换一个备用的
    const STATE_REFUND      = 'REFUND';     // 退单
    const STATE_EXPIRE      = 'EXPIRE';     // 过期
    const STATE_CLOSE       = 'CLOSE';      // 关闭

    /**
     * 支付方式
     */
    const PAYWAY_JSAPI      = 'JSAPI';      // 微信支付
    const PAYWAY_BANK       = 'BANK';       // 银行卡支付
    const PAYWAY_ALIPAY     = 'ALIPAY';     // 支付宝转账
    const PAYWAY_DEPOSIT    = 'DEPOSIT';    // 押金抵扣

    /**
     * 订单类型
     */
    const PAYTYPE_ROOM          = 'ROOM';           // 租房
    const PAYTYPE_DEIVCE        = 'DEIVCE';         // 设备
    const PAYTYPE_DEVICE        = 'DEIVCE';         // 设备
    const PAYTYPE_UTILITY       = 'UTILITY';        // 水电费
    const PAYTYPE_REFUND        = 'REFUND';         // 退房
    const PAYTYPE_RESERVE       = 'RESERVE';        // 预订
    const PAYTYPE_MANAGEMENT    = 'MANAGEMENT';     // 物业服务费
    const PAYTYPE_DEPOSIT_R     = 'DEPOSIT_R';      // 房租押金
    const PAYTYPE_DEPOSIT_O     = 'DEPOSIT_O';      // 其他押金
    const PAYTYPE_OTHER         = 'OTHER';          // 其他收费
    const PAYTYPE_WATER         = 'WATER';          // 水费
    const PAYTYPE_CLEAN         = 'CLEAN';          // 清洁费
    const PAYTYPE_ELECTRICITY   = 'ELECTRICITY';    // 电费
    const PAYTYPE_COMPENSATION  = 'COMPENSATION';   // 物品赔偿费
    const PAYTYPE_REPAIR        = 'REPAIR';         // 维修服务费

    /**
     * 首次 续费
     */
    const PAYSTATE_PAYMENT  = 'PAYMENT';    // 首次
    const PAYSTATE_RENEWALS = 'RENEWALS';   // 续费

    /**
     * 是否处理
     */
    const DEAL_DONE         = 'DONE';       // 处理
    const DEAL_UNDONE       = 'UNDONE';     // 未处理

    protected $table        = 'web_order';

    protected $fillable     = [
        'deal',
        'number',
        'sequence_number',
        'apartment_id',
        'room_type_id',
        'room_id',
        'employee_id',
        'resident_id',
        'customer_id',
        'money',
        'pay_type',
        'type',
        'other_id',
        'year',
        'month',
        'remark',
        'status',
        'paid',
        'pay_status',
    ];

    public function customer()
    {
        return $this->belongsTo(Customermodel::class, 'customer_id');
    }

    public function roomunion()
    {
        return $this->belongsTo(Roomunionmodel::class, 'room_id');
    }

    public function store()
    {
        return $this->belongsTo(Storemodel::class, 'store_id');
    }

    public function resident()
    {
        return $this->belongsTo(Residentmodel::class, 'resident_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employeemodel::class, 'employee_id');
    }

    public function roomtype()
    {
        return $this->belongsTo(Roomtypemodel::class, 'room_type_id');
    }

    /**
     * 生成随机数作为订单编号
     */
    public function getOrderNumber()
    {

        return date('YmdHis').mt_rand(1000000000, 9999999999);
        //return date('YmdHis').mt_rand(1, 100000);
    }

    /**
     * 检索当日确定的账单的数量
     */
    public function ordersConfirmedToday()
    {
        return $this->where('deal', self::DEAL_DONE)
            ->where('status', self::STATE_COMPLETED)
            ->whereDate('updated_at', '=', date('Y-m-d'))
            ->count();
    }
    //$sequence_number   = sprintf("%s%06d", date('Ymd'), $this->ordermodel->ordersConfirmedToday()+1);

    /**
     * 生成首次支付订单
     */
    public function firstCheckInOrders($resident,$roomunion){


        $info=[
            'number'         => $this->getOrderNumber(),
            'store_id'       => $roomunion->store_id,
            'room_type_id'   => $roomunion->room_type_id,
            'employee_id'    => $resident->employee_id,
            'uxid'           => $resident->uxid,
            'customer_id'    => $resident->customer_id,
            'room_id'        => $resident->room_id,
            'resident_id'    => $resident->id,
            'status'         => Ordermodel::STATE_PENDING,
            'pay_status'     => Ordermodel::PAYSTATE_PAYMENT,
            'pay_type'       => Ordermodel::PAYWAY_BANK,
            'deal'           => Ordermodel::DEAL_UNDONE,
            'created_at' => date('Y-m-d H:i:s',time()),
            'updated_at' => date('Y-m-d H:i:s',time()),
        ];



        $resident->begin_time   = Carbon::parse($resident->begin_time);
        $resident->end_time     = Carbon::parse($resident->end_time);

        $deposit_money          = $resident->deposit_money;     //押金
        $tmp_deposit            = $resident->tmp_deposit;       //其他押金


        //房租押金子订单
        if (0 < $deposit_money) {
            $info   = array_merge($info, [
                'money'     => $deposit_money,
                'paid'      => $deposit_money,
                'type'      => Ordermodel::PAYTYPE_DEPOSIT_R,
                'year'      => $resident->begin_time->year,
                'month'     => $resident->begin_time->month,
            ]);
            $this->insert($info);
        }

        //其他押金子订单
        if (0 < $tmp_deposit) {
            $info   = array_merge($info, [
                'money'     => $tmp_deposit,
                'paid'      => $tmp_deposit,
                'type'      => Ordermodel::PAYTYPE_DEPOSIT_O,
                'year'      => $resident->begin_time->year,
                'month'     => $resident->begin_time->month,
            ]);
            //Order::create($info);
            $this->insert($info);
        }

        //计算首次支付时的房租和物业费
        //当月还剩的天数
        $firstPay = $this->calcFirstPayMoney($resident);


        //生成物业服务费子订单
        if (0 < $resident->real_property_costs) {
            foreach ($firstPay as $bill) {
                $info   = array_merge($info, [
                    'type'      => Ordermodel::PAYTYPE_MANAGEMENT,
                    'year'      => $bill['year'],
                    'month'     => $bill['month'],
                    'money'     => $bill['management'],
                    'paid'      => $bill['management'],
                ]);
                $this->insert($info);
                //Order::create($info);
            }
        }

        //房租子订单
        if (0 < $resident->real_rent_money) {
            foreach ($firstPay as $bill) {
                $info   = array_merge($info, [
                    'type'      => Ordermodel::PAYTYPE_ROOM,
                    'year'      => $bill['year'],
                    'month'     => $bill['month'],
                    'money'     => $bill['rent'],
                    'paid'      => $bill['rent'],
                ]);
                $this->insert($info);
                // Order::create($info);
            }
        }
        return true;
    }

    /**
     * 计算并判断首次需要支付的几笔费用
     */
    private function calcFirstPayMoney($resident)
    {
        $beginTime          = $resident->begin_time;
        $payFrequency       = $resident->pay_frequency;
        $dateCheckIn        = $beginTime->day;
        $daysThatMonth      = $beginTime->copy()->endOfMonth()->day;
        $daysLeftOfMonth    = $daysThatMonth - $dateCheckIn + 1;
        $firstOfMonth       = $resident->begin_time->copy()->firstOfMonth();

        //当月剩余天数的订单
        $data[]     = array(
            'year'       => $beginTime->year,
            'month'      => $beginTime->month,
            'rent'       => ceil($resident->real_rent_money * $daysLeftOfMonth / $daysThatMonth),
            'management' => ceil($resident->real_property_costs * $daysLeftOfMonth / $daysThatMonth),
        );

        //如果是短租, 只生成当月的账单
        if ($resident->rent_type == Residentmodel::RENTTYPE_SHORT) {
            return $data;
        }

        if ($payFrequency > 1 OR $beginTime->day >= 21) {
            $i = 1;
            do {
                $tmpDate    = $firstOfMonth->copy()->addMonths($i);
                $data[] = array(
                    'year'       => $tmpDate->year,
                    'month'      => $tmpDate->month,
                    'rent'       => $resident->real_rent_money,
                    'management' => $resident->real_property_costs,
                );
            } while (++ $i < $resident->pay_frequency);
        }

        //如果是年付, 可能要有第13个月的账单
        if (12 == $payFrequency) {
            $endDate    = $resident->end_time;
            $endOfMonth = $endDate->copy()->endOfMonth();

            if ($endDate->day < $endOfMonth->day) {
                $data[] = array(
                    'year'          => $endDate->year,
                    'month'         => $endDate->month,
                    'rent'          => ceil($resident->real_rent_money * $endDate->day / $endOfMonth->day),
                    'management'    => ceil($resident->real_property_costs * $endDate->day / $endOfMonth->day),
                );
            }
        }
        return $data;
    }
}
