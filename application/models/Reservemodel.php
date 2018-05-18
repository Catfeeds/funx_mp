<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/18 0018
 * Time:        16:39
 * Describe:
 */
class Reservemodel extends Basemodel
{
    /**
     * 预约订单的状态
     */
    const STATE_BEGIN   = 'BEGIN';
    const STATE_WAIT    = 'WAIT';
    const STATE_END     = 'END';

    //预约类型, 现场, 电话, 微信预约
    const TYPE_VISIT    = 'VISIT';
    const TYPE_WECHAT   = 'WECHAT';
    const TYPE_PHONE    = 'PHONE';

    protected $table    = 'web_reserve';
    protected $dates    = ['time', 'created_at', 'updated_at'];
    protected $fillable = [
        'remark',
        'status',
        'require',
        'employee_id',
        'info_source',
        'work_address',
        'guest_type',
        'visit_by',
        'people_count',
        'source',
        'check_in_time',
    ];
}