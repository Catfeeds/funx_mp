<?php
/**
 * User: wws
 * Date: 2018-06-07
 * Time: 17:20
 * [web] 小业主收益model
 */
class Ownerearningmodel extends Basemodel
{
    public function __construct()
    {
        parent::__construct();
    }
    protected $table = 'boss_owner_earning';

    protected $hidden = ['created_at', 'updated_at','deleted_at'];

    /**
     * 小业主房间信息
     */
    public function house()
    {
        return $this->belongsTo(Ownerhousemodel::class, 'house_id')->select('id','number','status');
    }
}