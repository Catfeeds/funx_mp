<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * User: wws
 * Date: 2018-05-16
 * Time: 10:15
 * [web端]商城管理 --商品model
 */

class Goodsmodel extends Basemodel
{
    protected $table = 'boss_shop_goods';
    protected $hidden = ['created_at','updated_at','deleted_at'];

    public function category(){
        return $this->belongsTo(Goodscategorymodel::class,'category_id')->select('id','name');
    }

}