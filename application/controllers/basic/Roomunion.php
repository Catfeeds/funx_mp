<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/17 0017
 * Time:        17:29
 * Describe:    集中式房间
 */
class Roomunion extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public static function minPrice($store_id){
        return 1;
    }
}

