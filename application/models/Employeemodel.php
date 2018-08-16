<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/6/8
 * Time:        18:09
 * Describe:
 */
class Employeemodel extends Basemodel
{
    /*const POSITION_MANAGER = 'MANAGER';
    const POSITION_EMPLOYEE = 'EMPLOYEE';

    const STATE_Y = 'Y';
    const STATE_N = 'N';

    const TYPE_ADMIN = 'ADMIN';
    const TYPE_EMPLOYEE = 'EMPLOYEE';
    const TYPE_PRINCIPAL = 'PRINCIPAL';*/

    protected $table = 'boss_employee';
    //protected $fillable = ['name', 'phone', 'apartment_id', 'openid', 'unionid', 'nickname', 'sex', 'province', 'country', 'city', 'avatar'];


    /*public function store()
    {
        return $this->belongsTo(Storemodel::class, 'store_id');
    }*/

}