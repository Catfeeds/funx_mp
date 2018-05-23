<?php
/**
 * User: wws
 * Date: 2018-05-23
 * Time: 11:51
 */
class Roommodel extends Basemodel{

    /**
     * 房间的状态
     */
    const STATE_BLANK       = 'BLANK';      // 空
    const STATE_RESERVE     = 'RESERVE';    // 预订
    const STATE_RENT        = 'RENT';       // 出租
    const STATE_ARREARS     = 'ARREARS';    // 欠费
    const STATE_REFUND      = 'REFUND';     // 退房
    const STATE_OTHER       = 'OTHER';      // 其他 保留
    const STATE_OCCUPIED    = 'OCCUPIED';     // 房间被占用的状态, 可能是预约, 或者是办理入住后订单未确认之间的状态


    protected $table    = 'boss_room';
    protected $fillable = [
        'area',
        'layer',
        'number',
        'status',
        'end_time',
        'device_id',
        'begin_time',
        'rent_money',
        'resident_id',
        'people_count',
        'apartment_id',
        'room_type_id',
        'property_costs',
    ];

    const HALF = 'HALF';    //合租
    const FULL = 'FULL';    //整租



    protected $hidden   = [];

    //房间所属门店信息
    public function store(){

        return $this->belongsTo(Storemodel::class,'store_id');
    }

    //房间所属楼栋信息
    public function building(){

        return $this->belongsTo(Buildingmodel::class,'building_id');
    }

    //房间所属房型信息
    public function roomtype(){

        return $this->belongsTo(Roomtypemodel::class,'room_type_id');
    }

    //房屋公共智能设备
    public function housesmartdevice(){

        return $this->belongsTo(Smartdevicemodel::class,'house_smart_device_id');
    }

    //房间的智能设备
    public function smartdevice(){

        return $this->belongsTo(SmartDevicemodel::class,'smart_device_id');
    }

    //房间现在的住户信息
    public function resident(){

        return $this->belongsTo(Residentmodel::class,'resident_id');
    }

    //合租人信息
    public function unionresident(){

        return $this->hasMany(Unionresidentmodel::class,'room_id');
    }


}