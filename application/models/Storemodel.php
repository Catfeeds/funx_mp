<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/4/20 0020
 * Time:        16:18
 * Describe:    WEB
 * 门店表
 */

class Storemodel extends Basemodel{

    protected $table    = 'boss_store';

    /**
     * 该公寓签署合同的类型
     */
    const C_TYPE_FDD    = 'FDD';        //法大大电子合同
    const C_TYPE_NORMAL = 'NORMAL';     //线下纸质合同

    protected $fillable = [];

    protected $hidden   = ['updated_at','deleted_at'];

    //集中式房间
    public function roomunion()
    {
        return $this->hasMany(Roomunionmodel::class,'store_id');
    }

    //集中式房型
    public function roomtype()
    {
        return $this->hasMany(Roomtypemodel::class,'store_id');
    }
//    //门店所管辖的楼栋
//    public function building(){
//
//        return $this->hasMany(Buildingmodel::class,'store_id');
//    }
//
//    //门店的员工信息
//    public function employee(){
//
//        return $this->hasMany(employeemodel::class,'store_id');
//    }
//
//    //门店的房型
//    public function roomtype(){
//
//        return $this->hasMany(Roomtypemodel::class,'store_id');
//    }
//
//    //门店的房间
//    public function room(){
//
//        return $this->hasMany(Roommodel::class,'store_id');
//    }
    public function contracts()
    {
        return $this->hasMany(Contractmodel::class, 'store_id');
    }

    public function templateurl(){
        return $this->hasMany(Contracttemplatemodel::class, 'store_id');
    }
}
