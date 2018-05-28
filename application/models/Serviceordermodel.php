<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * User: wws
 * Date: 2018-05-15
 * Time: 10:30
 * [web端]维修-服务预约model
 */

class Serviceordermodel extends Basemodel
{
    protected $table    = 'boss_service_order';
    protected $hidden   = ['deleted_at'];

   /* public function roomunion(){
        return $this->hasMany(Roomunionmodel::class,'room_id')->select('id','number');
    }*/

    const SUBMITTED     = 'SUBMITTED';    //已提交
    const PENDING       = 'PENDING';      //待支付
    const PAID          = 'PAID';         //已支付
    const SERVING       = 'SERVING';      //处理中
    const COMPLETED     = 'COMPLETED';    //完成
    const CANCELED      = 'CANCELED';     //取消
}


