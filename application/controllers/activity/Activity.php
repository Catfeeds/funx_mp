<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/5/31
 * Time:        11:06
 * Describe:    优惠活动
 */
class Activity extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('activitymodel');
    }

}