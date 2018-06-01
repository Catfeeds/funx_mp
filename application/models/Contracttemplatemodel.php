<?php
/**
 * User: wws
 * Date: 2018-05-31
 * Time: 18:39
 *  [web]合同模板
 */
class Contracttemplatemodel extends Basemodel{

    protected $table    = 'boss_contract_template';

    protected $hidden   = ['created_at','updated_at','deleted_at'];


    public function templateurl(){

        return $this->hasMany(Storemodel::class,'store_id')->select('id');
    }

}