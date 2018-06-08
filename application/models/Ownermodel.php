<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * User: wws
 * Date: 2018-06-07
 * Time: 14:29
 * [web] 小业主model
 */
class Ownermodel extends Basemodel{

    protected $table  = 'boss_owner';
    protected $hidden = ['updated_at','deleted_at'];

    /**
     * 小业主房间信息
     */
    public function house()
    {
        return $this->belongsTo(Ownerhousemodel::class, 'house_id')->select('id','number','status');
    }

    /**
     * 小业主账单
     */
    public function earning()
    {
        return $this->hasMany(Ownerearningmodel::class, 'owner_id');
    }


}