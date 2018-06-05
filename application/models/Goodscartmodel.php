<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * User: wws
 * Date: 2018-05-17
 * Time: 15:53
 * [web端]商城购物车model
 */
class Goodscartmodel extends Basemodel
{
    protected $table    = 'boss_shop_cart';
    protected $hidden   = ['created_at','updated_at','deleted_at'];

    public function goods(){
        return $this->belongsTo(Goodsmodel::class,'goods_id')->select('id','name','shop_price','description','goods_thumb','quantity');
    }
    public function goodprice(){
        return $this->belongsTo(Goodsmodel::class,'goods_id')->select('id','shop_price');
    }

}