<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/9/27 0027
 * Time:        18:16
 * Describe:    用户参与吸粉活动的领奖记录
 */
class Attractcustomerprizemodel extends Basemodel
{
    protected $table    = 'boss_attract_customer_prize';

    protected $casts    = ['data'=>'array'];
}
