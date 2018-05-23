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

    //房间信息
    public function room(){

        return $this->belongsTo(Roommodel::class,'room_id');
    }

    //住户户信息
    public function resident(){

        return $this->belongsTo(Residentmodel::class,'resident_id');
    }


}