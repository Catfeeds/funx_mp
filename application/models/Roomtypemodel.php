<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/4/20 0020
 * Time:        16:16
 * Describe:    WEB
 * 房型表
 */
class Roomtypemodel extends Basemodel{

    protected $table    = 'boss_room_type';

    protected $fillable = [
        'store_id','name','feature','area','room_number','hall_number','toilet_number','provides',
        'toward','description','images',
    ];

    protected $hidden   = ['created_at','updated_at','deleted_at'];

    //房型的合同模板
    public function contracttemplate(){
        //保留历史模板，只显示最后更新的模板
        return $this->hasMany(Contracttemplatemodel::class,'room_type_id');
    }

    //房型的门店
    public function store(){
        return $this->belongsTo(Storemodel::class,'store_id');
    }

    //集中式房型下的房间
    public function roomunion()
    {
        return $this->hasMany(Roomunionmodel::class,'room_type_id');
    }


}
