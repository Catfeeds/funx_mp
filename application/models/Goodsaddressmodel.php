<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * User: wws
 * Date: 2018-05-17
 * Time: 09:30
 * [web端]商城管理 - 收货地址
 */
class Goodsaddressmodel extends Basemodel
{
    protected $table    = 'boss_shop_address';
    protected $hidden   = ['created_at','updated_at','deleted_at'];


    public function address(){
        return $this->belongsTo(Ordermodel::class,'_id')->select('id');
    }
}