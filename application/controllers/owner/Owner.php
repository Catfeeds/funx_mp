<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * User: wws
 * Date: 2018-06-07
 * Time: 14:27
 * [web] 小业主
 */
class Owner extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('ownermodel');
    }

    /**
     * 小业主信息
     */
    public function ownerList()
    {
        $this->load->model('ownerhousemodel');
        $uxid = 3;
        $field = ['id', 'name', 'created_at', 'house_id'];
        if (isset($uxid)) {
            $owner = Ownermodel::with('house')->where('customer_id',$uxid)->get($field);
            $this->api_res(0, ['list' => $owner]);
        } else {
            $this->api_res(1005);
        }
    }

   
}