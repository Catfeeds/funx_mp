<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/4/20 0020
 * Time:        16:07
 * Describe:    BOSS
 * 合同表
 */
class Contractmodel extends Basemodel {

    protected $table    = 'boss_contract';

    protected $fillable = [
        'status',
        'view_url',
        'contract_id',
        'resident_id',
        'download_url',
    ];


    /**
     * 合同签署类型
     */
    const SIGN_NEW  = 'NEW'; //新签
    const SIGN_RENEW  = 'RENEW'; //续签
    const SIGN_CHANGE  = 'CHANGE'; //换房

    /**
     * 合同的状态
     */
    const STATUS_GENERATED = 'GENERATED';       //合同已经生成
    const STATUS_SIGNING   = 'SIGNING';         //双方签署过程中
    const STATUS_ARCHIVED  = 'ARCHIVED';        //合同归档

    /**
     * 合同的类型, 电子合同还是纸质合同
     */
    const TYPE_FDD         = 'FDD';
    const TYPE_NORMAL      = 'NORMAL';


    protected $hidden   = [];

    //社区名
    public function store(){

        return $this->belongsTo(Storemodel::class,'store_id')->select('id','name');
    }

    //房间号
    public function roomunion(){

        return $this->belongsTo(Roomunionmodel::class,'room_id')->select('id','number');
    }


    //房间信息
    public function room(){

        return $this->belongsTo(Roommodel::class,'room_id');
    }

    //住户户信息
    public function resident(){

        return $this->belongsTo(Residentmodel::class,'resident_id');
    }

    /**
     * 合同的签署交易记录
     */
    public function transactions()
    {
        return $this->hasMany(Fddrecordmodel::class, 'contract_id');
    }


}