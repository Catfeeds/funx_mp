<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/5/21
 * Time:        14:13
 * Describe:    预约看房
 */
class Reserveordermodel extends Basemodel
{
    protected $table = 'boss_reserve_order';
    protected $hidden= ['created_at','updated_at','deleted_at'];
    protected $fillable = ['store_id','room_type_id','name','phone','time'];

}