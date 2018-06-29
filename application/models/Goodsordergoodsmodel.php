<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * User: wws
 * Date: 2018-05-15
 * Time: 10:30
 * [web端]商品订单model
 */
class Goodsordergoodsmodel extends Basemodel
{
    protected $table    = 'boss_shop_order_goods';
    protected $hidden   = ['deleted_at'];


    public function goods(){
        return$this->belongsTo(Goodsordermodel::class,'order_id')->select('id','number','');
    }


}