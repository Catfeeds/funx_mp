<?php
/**
 * User: wws
 * Date: 2018-05-29
 * Time: 16:17
 * [web端]个人中心 - 合同信息
 */
class Contract extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('contractmodel');
    }

    public function contract()
    {
        $this->load->model('storemodel');
        $this->load->model('roomunionmodel');
        $post = $this->input->post(null, true);
        //$uxid = intval(strip_tags(trim($post['uxid'])));
        $uxid = CURRENT_ID;
        $field = ['id','store_id', 'room_id','view_url'];

        if (isset($uxid)) {
            $contract = Contractmodel::with('store')->with('roomnum')->where('uxid',$uxid)->get($field);
            $this->api_res(0,[ 'contract'=>$contract]);
        } else {
            $this->api_res(1005);
        }



    }




}