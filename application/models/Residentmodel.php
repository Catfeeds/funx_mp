<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Carbon\Carbon;
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/4/20 0020
 * Time:        16:15
 * Describe:    BOSS
 * 住户表
 */
class Residentmodel extends Basemodel{

    const CARD_IDCARD = 'IDCARD';               //身份证
    const CARD_OTHER  = 'OTHER';                //其他
    const CARD_ZERO   = '0';                    //身份证
    const CARD_ONE    = '1';                    //护照
    const CARD_TWO    = '2';                    //军人身份证
    const CARD_SIX    = '6';                    //社会保障卡
    const CARD_A      = 'A';                    //武装警察身份证件
    const CARD_B      = 'B';                    //港澳通行证
    const CARD_C      = 'C';                    //台湾居民来往大陆通行证
    const CARD_E      = 'E';                    //户口簿
    const CARD_F      = 'F';                    //临时居民身份证
    const CARD_P      = 'P';                    //外国人永久居留证
    const CARD_YYZZ   = 'BL';                   //营业执照(BUSINESS_LICENSE)

    const STATE_RESERVE         = 'RESERVE';            //预约
    const STATE_NORMAL          = 'NORMAL';             //正常状态
    const STATE_NOTPAY          = 'NOT_PAY';            //未支付
    const STATE_NORMAL_REFUND   = 'NORMAL_REFUND';      //正常退房
    const STATE_UNDER_CONTRACT  = 'UNDER_CONTRACT';     //违约退房
    const STATE_RENEWAL         = 'RENEWAL';            //续租
    const STATE_CHANGE_ROOM     = 'CHANGE_ROOM';        //换房
    const STATE_INVALID         = 'INVALID';            //有缴费订单住户, 未入住, 标记为无效

    const RENTTYPE_SHORT    = 'SHORT';
    const RENTTYPE_LONG     = 'LONG';

    const TYPE_FIRST   = 'FIRST'; //首次入住住户
    const TYPE_RENEWAL   = 'RENEWAL'; //续租住户



    protected $table   = 'boss_resident';


    protected $fillable    = [
        'store_id',
        'book_money',
        'book_time',
        'room_id',
        'employee_id',
        'name',
        'phone',
        'card_type',
        'card_number',
        'address',
        'name_two',
        'phone_two',
        'card_type_two',
        'card_number_two',
        'begin_time',
        'end_time',
        'people_count',
        'alternative',
        'alter_phone',
        'special_term',
        'card_one',
        'card_two',
        'card_three',
        'pay_frequency',
        'real_rent_money',
        'real_property_costs',
        'discount_id',
        'first_pay_money',
        'contract_time',
        'rent_type',
        'discount_money',
        'remark',
        'deposit_month',
        'deposit_money',
        'tmp_deposit',
        'status',
        'data',
    ];

    protected $casts    = [
        'data' => 'array',
    ];

    protected $dates    = [
        'book_time',
        'refund_time',
        'begin_time',
        'end_time',
        'reserve_begin_time',
        'reserve_end_time',
        'created_at',
        'updated_at',
    ];

    protected $hidden  = [];

    //住户的房间信息
    public function roomunion(){

        return $this->belongsTo(Roomunionmodel::class,'room_id');
    }

    //住户的订单
    public function orders(){

        return $this->hasMany(Ordermodel::class,'resident_id');
    }

    //
    public function neworders(){

        return $this->hasMany(Newordermodel::class,'resident_id');
    }

    //住户的入住合同信息
    public function contract(){

        return $this->hasMany(Contractmodel::class,'resident_id')->where('rent_type','!=',Contractmodel::RENT_RESERVE);
    }

    //住户预定合同
    public function reserve_contract()
    {
        return $this->hasMany(Contractmodel::class,'resident_id')->where('rent_type',Contractmodel::RENT_RESERVE);
    }

    //住户的所有合同
    public function contracts(){
        return $this->hasMany(Contractmodel::class,'resident_id');
    }

    //住户的用户信息
    public function customer(){

        return $this->belongsTo(Customermodel::class,'customer_id');
    }

    //住户的优惠券
    public function  coupons()
    {
        return $this->hasMany(Couponmodel::class,'resident_id');
    }

    //活动折扣
    public function discount()
    {
        return $this->belongsTo(Activitymodel::class, 'discount_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employeemodel::class, 'employee_id');
    }


    public function roomunion1(){

        return $this->belongsTo(Roomunionmodel::class,'room_id')->select('id','area','number') ;
    }

    public function store(){

        return $this->belongsTo(storemodel::class,'store_id') ;
    }


    /**
     * 计算用户的合同结束时间
     * 主要是考虑到, 租房合同开始日期是某个月的月底而结束月份是2月份的情况
     */
    public function contractEndDate($checkInDateStr, $contractTime)
    {
        $checkInDate    = Carbon::parse($checkInDateStr);

        return $this->addMonths($checkInDate, $contractTime);
    }

    /**
     * 计算指定个月后的今天的日期
     * 比如, 1月31日的一个月后可能是2月28号也可能是2月29号
     */
    public function addMonths(Carbon $date, $months = 1)
    {
        $endMonth       = $date
            ->copy()
            ->startOfMonth()
            ->addMonths($months)
            ->endOfMonth();

        if ($endMonth->day >= $date->day - 1) {
            $endTime = $endMonth->startOfMonth()->addDays($date->day - 2);
        }

        return isset($endTime) ? $endTime : $endMonth;
    }
    /**
     * @param Resident $resident
     * @return array
     */
    public function transform($resident)
    {
        $data   = [
            'id'                    => $resident->id,
            'name'                  => $resident->name,
            'card_type'             => $resident->card_type,
            'card_number'           => $resident->card_number,
            'phone'                 => $resident->phone,
            'address'               => $resident->address,
            'emergency_name'        => $resident->alternative,
            'emergency_phone'       => $resident->alter_phone,
            'card_one_url'          => $resident->card_one,
            'card_two_url'          => $resident->card_two,
            'card_three_url'        => $resident->card_three,
            'rent_price'            => $resident->real_rent_money,
            'management_price'      => $resident->real_property_costs,
            'deposit_money_rent'    => $resident->deposit_money,
            'deposit_money_other'   => $resident->tmp_deposit,
            'deposit_month'         => $resident->deposit_month,
            'contract_time'         => $resident->contract_time,
            'rent_type'             => $resident->rent_type,
            'pay_type'              => $resident->pay_frequency,
            'first_pay'             => $resident->first_pay_money,
            'status'                => $resident->status,
            'remark'                => $resident->remark,
            'created_at'            => Carbon::parse($resident->created_at)->toDateTimeString(),
            'updated_at'            => Carbon::parse($resident->updated_at)->toDateTimeString(),
        ];

        if (0 < $resident->contract_time) {
            $data['begin_time']     = Carbon::parse($resident->begin_time)->toDateString();
            $data['end_time']       = Carbon::parse($resident->end_time)->toDateString();
        }

        if (0 < $resident->reserve_contract_time) {
            $data['reserve_begin_time']     = Carbon::parse($resident->reserve_begin_time)->toDateString();
            $data['reserve_end_time']       = Carbon::parse($resident->reserve_end_time)->toDateString();
        }

        if (self::STATE_NORMAL == $resident->status) {
            $data['days_left']  = Carbon::now()->startOfDay()->diffIndays($resident->end_time, false);
        }

        if (!empty($resident->name_two)) {
            $data   = array_merge($data, [
                'mate'  => [
                    'name'         => $resident->name_two,
                    'phone'        => $resident->phone_two,
                    'card_type'    => $resident->card_type_two,
                    'card_number'  => $resident->card_number_two,
                ],
            ]);
        }
        //订单详情
         if($resident->orders){
             $data   = array_merge($data, [
                 'orders'  => $resident->orders,
             ]);
         }

        if (0 < $resident->discount_id) {
            $activity   = $resident->discount;
            $data       = array_merge($data, [
                'rent_discount'     => [
                    'id'        => $activity->id,
                    'name'      => $activity->name,
                    'discount'  => $activity->coupontypes()->first()->discount,
                ]
            ]);
        }

        $data['avatar']     = null;
        if (0 < $resident->customer_id) {
            //$data['avatar'] = $resident->customer->avatar;
        }

        if (0 < $resident->room_id) {
            $room   = $resident->roomunion;
            $data   = array_merge($data, [
                'room'      => [
                    'id'                => $room->id,
                    'number'            => $room->number,
                    'status'            => $room->status,
                    //'status_name'       => config('strongberry.room.status')[$room->status],
                    'people_count'      => $room->people_count,
                    'rent_price'        => $room->rent_price,
                    'property_price'    => $room->property_price,
                    'area'              => $room->area,
                    'store_id'          => $room->store->id,
                    'store_name'        => $room->store->name,
                ]
            ]);
        }

        if (0 < $resident->book_money) {
            $data['booking'] = [
                'money' => $resident->book_money,
                'time'  => $resident->book_time->format('Y-m-d'),
            ];
            $data['money'] = $resident->book_money;
            $data['time']  = $resident->book_time->format('Y-m-d');
        }

        if ($contract = $resident->contract()->first()) {
            $data   = array_merge($data, [
                'contract'  => [
                    'id'        => $contract->id,
                    'status'    => $contract->status,
                    'type'      => $contract->type,
                    'view_url'  => $contract->view_url,
                ],
            ]);
        }

        if ($reserve_contract = $resident->reserve_contract()->first()) {
            $data   = array_merge($data, [
                'reserve_contract'  => [
                    'id'        => $reserve_contract->id,
                    'status'    => $reserve_contract->status,
                    'type'      => $reserve_contract->type,
                    'view_url'  => $reserve_contract->view_url,
                ],
            ]);
        }

        return $data;
    }


    /**
     * 检索住户
     */
    public function queryIndex($query, array $ids = [])
    {
        $perPage    = isset($query['per_page']) ? $query['per_page'] : config('strongberry.pageSize');
        $residents  = Resident::with('customer')->whereIn('id', $ids);

        if (isset($query['status']) && in_array($query['status'], [
                Residentmodel::STATE_RESERVE,
                Residentmodel::STATE_NORMAL,
                Residentmodel::STATE_NOTPAY,
                Residentmodel::STATE_NORMAL_REFUND,
                Residentmodel::STATE_UNDER_CONTRACT,
                Residentmodel::STATE_RENEWAL,
                Residentmodel::STATE_CHANGE_ROOM,
            ])) {
            $residents  = $residents->where('status', $query['status']);
        }

        $residents  = $residents->orderBy('end_time', 'ASC')->orderBy('room_id', 'ASC')->paginate($perPage);

        return $residents;
    }




}
