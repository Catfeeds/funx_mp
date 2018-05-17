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

    protected $fillable = [];

    protected $hidden   = ['updated_at','deleted_at'];

    public function roomUnion()
    {
        return $this->hasMany(Roomunionmodel::class,'store_id');
    }

    public function roomType()
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
//        return $this->hasMany(Employeemodel::class,'store_id');
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

}
