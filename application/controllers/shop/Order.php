<?php
/**
 * User: wws
 * Date: 2018-06-04
 * Time: 11:55
 * [web]查看账单 - 个人页面
 */
class Order extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('ordermodel');
    }

    public function orderlist()
    {
        $this->load->model('storemodel');
        $this->load->model('roomunionmodel');

        $post = $this->input->post(null, true);
        //$uxid = intval(strip_tags(trim($post['uxid'])));
        $uxid = 7;
        $field = ['id','store_id', 'room_id',''];

        if (isset($uxid)) {
            $contract = Ordermodel::with('storename')->with('roomnum')->where('uxid',$uxid)->get($field);
            $this->api_res(0,[ 'contract'=>$contract]);
        } else {
            $this->api_res(1005);
        }
    }

    /**
     *  查看账单
     */
    public function orderux()
    {
        $this->load->model('');
    }

}
