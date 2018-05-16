<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * User: wws
 * Date: 2018-05-16
 * Time: 10:15
 * [web端]商城管理 --商品分类 model
 */
class Goodscategorymodel extends Basemodel
{
    protected $table    = 'boss_shop_category';
    protected $hidden   = ['created_at','updated_at','deleted_at'];

    public function goods(){
        return $this->hasMany(Goodsmodel::class,'category_id')->select('id','category_id','name','shop_price','description','goods_thumb');
    }
}