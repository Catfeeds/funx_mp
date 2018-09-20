<?php
defined('BASEPATH') or exit('No direct script access allowed');
use Carbon\Carbon;
use EasyWeChat\Foundation\Application;

/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/7/30 0030
 * Time:        11:01
 * Describe:
 */
class Taskflowmodel extends Basemodel
{
    protected $CI;
    const STATE_AUDIT      = 'AUDIT';
    const STATE_APPROVED   = 'APPROVED';
    const STATE_UNAPPROVED = 'UNAPPROVED';
    const STATE_CLOSED     = 'CLOSED';

    const TYPE_CHECKOUT = 'CHECKOUT';
    const TYPE_PRICE    = 'PRICE';
    const TYPE_RESERVE  = 'RESERVE';
    const TYPE_SERVICE  = 'SERVICE';

    const CREATE_EMPLOYEE = 'EMPLOYEE';
    const CREATE_CUSTOMER = 'CUSTOMER';

    protected $table = 'boss_taskflow';

    protected $fillable = [
        'company_id', 'name', 'type', 'description',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->CI = &get_instance();
    }

    /**
     * 发起员工
     */
    public function employee()
    {
        return $this->belongsTo(Employeemodel::class, 'employee_id');
    }

    /**
     * 最近操作的步骤
     */
    public function step()
    {
        return $this->belongsTo(Taskflowstepmodel::class, 'step_id');
    }

    /**
     * 审核步骤
     */
    public function steps()
    {
        return $this->hasMany(Taskflowstepmodel::class, 'taskflow_id');
    }

    /**
     * 审核记录
     */
    public function record()
    {
        return $this->hasMany(Taskflowrecordmodel::class, 'taskflow_id');
    }

    /**
     * 退房的信息
     */
    public function checkout()
    {
        return $this->hasOne(Checkoutmodel::class, 'taskflow_id');
    }

    /**
     * 调价的信息
     */
    public function price()
    {
        return $this->hasOne(Pricecontrolmodel::class, 'taskflow_id');
    }

    /**
     * 服务订单
     */
    public function service()
    {
        return $this->hasOne(Serviceordermodel::class, 'taskflow_id');
    }

    /**
     * 门店
     */
    public function store()
    {
        return $this->belongsTo(Storemodel::class, 'store_id');
    }

    /**
     * 房间
     */
    public function roomunion()
    {
        return $this->belongsTo(Roomunionmodel::class, 'room_id');
    }

    /**
     * 生成审批编号
     */
    public function newNumber($store_id)
    {
        $count = $this
            ->withTrashed()
            ->where('store_id', $store_id)
            ->whereDate('created_at', date('Y-m-d'))
            ->count();
        $newCount      = $count + 1;
        $serial_number = date('Ymd') . sprintf('%05s', $store_id) . sprintf('%05s', $newCount);
        return $serial_number;
    }

    /**
     * 创建退房的同时创建退款的任务流
     */
    public function createTaskflow($type, $store_id, $room_type_id = null, $room_id = null, $msg = '')
    {
        $this->CI->load->model('taskflowtemplatemodel');
        $this->CI->load->model('taskflowstepmodel');
        $this->CI->load->model('taskflowsteptemplatemodel');
        log_message('debug', 'company_id' . get_instance()->company_id);
        $template = Taskflowtemplatemodel::where('company_id', get_instance()->company_id)
            ->where('type', $type)
            ->first();
        if (empty($template)) {
            return null;
        }
        $step_field    = ['id', 'company_id', 'name', 'type', 'seq', 'position_ids', 'employee_ids'];
        $step_template = $template->step_template()->get($step_field);
        if (empty($step_template->toArray())) {
            return null;
        }
        $taskflow = new Taskflowmodel();
        $taskflow->fill($template->toArray());
        $taskflow->template_id   = $template->id;
        $taskflow->serial_number = $taskflow->newNumber($store_id);
        $taskflow->store_id      = $store_id;
        $taskflow->create_role   = Taskflowmodel::CREATE_CUSTOMER;
        $taskflow->customer_id   = $this->CI->user->id;
        $taskflow->status        = Taskflowmodel::STATE_AUDIT;
        $taskflow->room_type_id  = $room_type_id;
        $taskflow->room_id       = $room_id;
        $taskflow->save();
        $step_template_keys_transfer = ['step_template_id', 'company_id', 'name', 'type', 'seq', 'position_ids', 'employee_ids'];
        $step_template_arr           = $step_template->toArray();
        $step_merge_data             = [
            'store_id'    => $store_id,
            'taskflow_id' => $taskflow->id,
            'status'      => Taskflowstepmodel::STATE_AUDIT,
            'created_at'  => Carbon::now()->toDateTimeString(),
            'updated_at'  => Carbon::now()->toDateTimeString(),
        ];
        $result = [];
        foreach ($step_template_arr as $step) {
            $step_combine = array_combine($step_template_keys_transfer, $step);
            $result[]     = array_merge($step_merge_data, $step_combine);
        }
        Taskflowstepmodel::insert($result);

        $this->notify($taskflow->type, $msg, $this->listEmployees($taskflow->id));

        return $taskflow->id;
    }

    protected function notify($type, $msg, $employees)
    {
        log_message('debug', $type . ' notify  ' . $msg);
        switch ($type) {
            case self::TYPE_RESERVE:
                $this->sendReserveMsg(json_decode($msg), $employees);
                break;
            case self::TYPE_SERVICE:
                $this->sendServiceMsg(json_decode($msg), $employees);
                break;
            default:
                break;
        }
    }

    protected function listEmployees($taskflow_id)
    {
        $audit = Taskflowstepmodel::where('status', '!=', Taskflowstepmodel::STATE_APPROVED)
            ->where('taskflow_id', $taskflow_id)
            ->first();
        $this->CI->load->model('employeemodel');
        $employee_list = Employeemodel::whereIn('position_id', explode(',', $audit['position_ids']))
            ->get();

        $ret = [];
        foreach ($employee_list as $employee) {
            $store_arr = explode(',', $employee['store_ids']);
            if (!in_array($audit['store_id'], $store_arr)) {
                continue;
            }
            $ret[] = $employee;
        }

        return $ret;
    }

    /**
     * {{first.DATA}}
     * 客户姓名：{{keyword1.DATA}}
     * 客户手机：{{keyword2.DATA}}
     * 预约时间：{{keyword3.DATA}}
     * 预约内容：{{keyword4.DATA}}
     * {{remark.DATA}}
     * form参数
     * store_id: 门店id
     * name: 预约用户姓名
     * phone: 预约用户手机
     * visit_time: 预约时间
     * content: 预约内容
     */
    protected function sendReserveMsg($body, $employees = [])
    {
        $data = [
            'first'    => '有新的预约消息',
            'keyword1' => $body->name,
            'keyword2' => $body->phone,
            'keyword3' => $body->visit_time,
            'keyword4' => '看房预约',
            'remake'   => '如有疑问请与工作人员联系',
        ];
        if (!empty($body->content)) {
            $data['keyword4'] = $body->content;
        }

        // $this->CI->load()
        $this->CI->load->helper('wechat');
        $app = new Application(getWechatEmployeeConfig());

        foreach ($employees as $employee) {
            if (null == $employee['employee_mp_openid']) {
                log_message('error', '找不到openid');
                continue;
            }
            try {
                log_message('debug', 'try to 预约发送模版消息');
                $app->notice->send([
                    'touser' => $employee['employee_mp_openid'],
                    'template_id' => config_item('tmplmsg_employee_Reserve'),
                    'data' => $data,
                    "miniprogram"=> [
                        "appid"=> config_item('miniAppid'),
                        "pagepath"=>"/pages/index/homePage"
                    ],
                ]);
                log_message('info', '微信回调成功发送模板消息: ' . $employee->name);
            } catch (Exception $e) {
                log_message('error', '租户预约模板消息通知失败：' . $e->getMessage());
//                throw $e;
                return;
            }
        }
    }

    protected function sendServiceMsg($body, $employees = [])
    {
        $data = [
            'first'     => "有新的服务订单",
            'keyword1'  => "{$body->type}服务",
            'keyword2'  => "{$body->name}-{$body->phone}",
            'keyword3'  => date('Y-m-d H:i:s'),
            'keyword4'  => "{$body->store}-{$body->number}",
            'remark'    => '请尽快处理!',
        ];
        // $this->CI->load()
        $this->CI->load->helper('wechat');
        $app = new Application(getWechatEmployeeConfig());

        foreach ($employees as $employee) {
            if (null == $employee['employee_mp_openid']) {
                log_message('error', '找不到openid');
                continue;
            }
            try {
                log_message('debug', 'try to 服务订单发送模板消息');
                $app->notice->send([
                    'touser' => $employee['employee_mp_openid'],
                    'template_id' => config_item('tmplmsg_employee_TaskRemind'),
                    'data' => $data,
                    "miniprogram"=> [
                        "appid"=> config_item('miniAppid'),
                        "pagepath"=>"/pages/index/homePage"
                    ],
                ]);
                log_message('info', '微信回调成功发送模板消息: ' . $employee->name);
            } catch (Exception $e) {
                log_message('error', '租户服务订单模板消息通知失败：' . $e->getMessage());
//                throw $e;
                return;
            }
        }
    }


}
