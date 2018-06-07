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

    /**
     * 查看小业主账单
     */
    public function bill()
    {
        $this->load->model('ownerearningmodel');
        $this->load->model('ownerhousemodel');
        $post = $this->input->post(null, true);
        $number = trim($post['number']);
        $uxid = 3;
        // echo 1;die();
        $field = ['id', 'house_id'];
        if (isset($uxid)) {
            $aa = Ownerhousemodel::where('number', $number)->get()->map(function($id){
                return $id->number;});
            var_dump($aa);die();
//            if (!empty($aa)){
//                $bill = Ownermodel::with('earning')->with('house')->where('customer_id',$uxid)->get($field);
//                //var_dump($bill);die();                           //CURRENT_ID
//                $this->api_res(0, ['list' => $bill]);
//            }else{
//                $this->api_res(1005);
//            }
        } else {
            $this->api_res(1005);
        }

    }
}