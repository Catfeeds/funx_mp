<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/28 0028
 * Time:        9:31
 * Describe:
 */
class Employeemodel extends Basemodel{
    const POSITION_MANAGER  = 'MANAGER';
    const POSITION_EMPLOYEE = 'EMPLOYEE';

    const STATE_Y           = 'Y';
    const STATE_N           = 'N';

    const TYPE_ADMIN        = 'ADMIN';
    const TYPE_EMPLOYEE     = 'EMPLOYEE';
    const TYPE_PRINCIPAL    = 'PRINCIPAL';

    protected $table        = 'employees';
    protected $fillable     = ['name','phone','apartment_id','openid', 'unionid', 'nickname', 'sex', 'province', 'country', 'city', 'avatar'];


    public function store(){
        return $this->belongsTo(Storemodel::class, 'store_id');
    }

}
