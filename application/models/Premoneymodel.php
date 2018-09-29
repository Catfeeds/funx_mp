<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/9/29 0029
 * Time:        11:38
 * Describe:    用户预存金
 */
class Premoneymodel extends Basemodel
{
    protected $table    = 'boss_pre_money';

    /**
     * @param $customer_id
     * 用户的预存金
     */
    public static function premoney($premoney)
    {
        if(empty($premoney->money)){
            $money  = 0;
        }else{
            $money  = $premoney->money;
        }
        return $money;
    }

}
