<?php
/**
 * User: wws
 * Date: 2018-06-01
 * Time: 09:31
 *  [ ]
 */
class Goodsordermodel extends Basemodel
{
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

    protected $table  = 'boos_shop_order';

    protected $hidden = ['created_at','updated_at','deleted_at'];

    public function address(){
        return $this->hasMany(Goodsaddressmodel::class,'address_id')->select('id');
    }

}