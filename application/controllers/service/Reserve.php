<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/5/21
 * Time:        14:10
 * Describe:    预约看房
 */
class Reserve extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('reserveordermodel');
    }

    /*
     * 生成新预约订单
     */
    public function reserve()
    {
        $post = $this->input->post(null, true);
        if (!$this->validation()) {
            $fieldarr = ['store_id', 'room_type_id', 'name', 'phone', 'visit_time'];
            $this->api_res(1002, ['errmsg' => $this->form_first_error($fieldarr)]);
            return;
        }
        $has_reserve    = Reserveordermodel::where('phone',$post['phone'])->orderBy('created_at','desc')->first();
        if ($has_reserve) {
            if ((strtotime($has_reserve->created_at)+(10*60))>time()){
                $this->api_res(10023);
                return;
            }
        }
        $reserve = new Reserveordermodel();
        $reserve->fill($post);
        $reserve->customer_id = CURRENT_ID;
        $reserve->time        = date('Y-m-d H:i:s', time());
        $reserve->visit_by    = 'WECHAT';
        $reserve->status      = 'WAIT';

        //任务流流程
        $this->load->model('taskflowtemplatemodel');
        $template = Taskflowtemplatemodel::where('company_id', $this->user->company_id)->where('type', Taskflowtemplatemodel::TYPE_RESERVE)->first();
        if ($template) {
            $this->load->model('taskflowmodel');
            $taskflow_id = $this->taskflowmodel->createTaskflow(
                Taskflowmodel::TYPE_RESERVE,
                $post['store_id'],
                $post['room_type_id'],
                null,
                json_encode([
                    "name"       => $post['name'],
                    "phone"      => $post['phone'],
                    "visit_time" => $post['visit_time'],
                ]));

            $reserve->taskflow_id = $taskflow_id;
        }

        if ($reserve->save()) {
            $this->api_res(0);
        } else {
            $this->api_res(1009);
        }
    }

    /**
     * 预约过的房源
     */
    public function precontract()
    {
        $this->load->model('roomunionmodel');
        $this->load->model('roomtypemodel');
        $this->load->model('employeemodel');
        $filed       = ['id', 'room_type_id', 'room_id', 'employee_id'];
        $precontract = Reserveordermodel::with('room')->with('room_type')->with('employee')
            ->where('customer_id', CURRENT_ID)
            ->whereIn('status', ['WAIT', 'BEGIN'])->get($filed)
            ->toArray();
        for ($i = 0; $i < count($precontract); $i++) {
            $images                                 = $precontract[$i]['room_type']['images'];
            $imageArray                             = json_decode($images, true);
            $precontract[$i]['room_type']['images'] = $this->fullAliossUrl($imageArray, true);
        }
        $this->api_res(0, ['list' => $precontract]);
    }

    /**
     * 看过的房源
     */
    public function visited()
    {
        $this->load->model('roomunionmodel');
        $this->load->model('roomtypemodel');
        $this->load->model('employeemodel');
        $filed       = ['id', 'room_type_id', 'room_id', 'employee_id'];
        $precontract = Reserveordermodel::with('room')->with('room_type')->with('employee')
            ->where('customer_id', CURRENT_ID)
            ->where('status', 'END')->get($filed)
            ->map(function ($item) {
                if (isset($item->room_type->images)) {
                    $images                      = $item->room_type->images;
                    $imageArray                  = json_decode($images, true);
                    $item['room_type']['images'] = $this->fullAliossUrl($imageArray, true);
                }
                return $item;
            })->toArray();
        $this->api_res(0, ['list' => $precontract]);
    }

    /**
     * 表单验证
     */
    public function validation()
    {
        $this->load->library('form_validation');
        $config = array(
            array(
                'field' => 'store_id',
                'label' => '门店ID',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'room_type_id',
                'label' => '房型ID',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'name',
                'label' => '姓名',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'phone',
                'label' => '联系电话',
                'rules' => 'trim|required|max_length[13]',
            ),
            array(
                'field' => 'visit_time',
                'label' => '预约时间',
                'rules' => 'trim|required',
            ),
        );
        $this->form_validation->set_rules($config)->set_error_delimiters('', '');
        return $this->form_validation->run();
    }
}
