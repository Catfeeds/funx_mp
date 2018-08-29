<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Illuminate\Database\Capsule\Manager as DB;
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/8/29 0029
 * Time:        16:11
 * Describe:    预定
 */
class Reserve extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('fadada');
        $this->load->helper('common');
        $this->load->model('contractmodel');
        $this->load->model('contracttemplatemodel');

    }

    /**
     * 预定的合同确认
     */
    public function contractConfirm()
    {
        $input  = $this->input->post(null,true);
        $resident_id    = intval(strip_tags($input['resident_id']));
        $phone          = trim(strip_tags($input['phone']));
        //验证短信验证码
        $this->load->library('m_redis');
        if (!$this->m_redis->verifyResidentPhoneCode($input['phone'],$input['code'])) {
            $this->api_res(10008);
            return;
        }
        $this->load->model('residentmodel');
        $this->load->model('roomunionmodel');
        $this->load->model('storemodel');
        $this->load->model('roomtypemodel');
        $this->load->model('contarctmodel');
        $this->load->model('ordermodel');
        $this->load->model('fddrecordmodel');
        $resident   = Residentmodel::find($resident_id);
        if (!$resident) {
            $this->api_res(1007);
            return;
        }
        if ($resident->phone!=$phone) {
            $this->api_res(10010);
            return;
        }
        $roomunion   = $resident->roomunion;
        if($roomunion->status!=Roomunionmodel::STATE_OCCUPIED){
            $this->api_res(10014);
            return;
        }
        $has_contarct   = $resident->reserve_contract()->whereIn('status', [Contractmodel::STATUS_ARCHIVED,Contractmodel::STATUS_SIGNING]);
        if ($has_contarct->exists()) {
            $this->api_res(10015);
            return;
        }
        $reserve_contract   = $resident->reserve_contract;
        $contract_type  = $resident->store->contract_type;
        //默认跳转的页面 账单列表
        $targetUrl  = '';
        if (Storemodel::C_TYPE_NORMAL==$contract_type) {
            if (empty($reserve_contract)) {
                try {
                    DB::beginTransaction();
                    //生成纸质版合同
                    $contract   = $this->contractPaper($resident);
                    //生成预定定金账单
                    $order  = $this->createReserveOrder($resident,$roomunion);
                    if ($contract&&$order) {
                        DB::commit();
                    } else {
                        DB::rollBack();
                    }
                } catch (Exception $e) {
                    DB::rollBack();
                    throw $e;
                }
            }
        } else {
            if (empty($reserve_contract)) {
                try {
                    DB::beginTransaction();
                    //生成电子合同
                    $reserve_contract   = $this->contractFdd($resident);
                    //生成预定订单
                    $order  = $this->createReserveOrder($resident,$roomunion);
                    if ($reserve_contract&&$order) {
                        DB::commit();
                    } else {
                        DB::rollBack();
                    }
                } catch (Exception $e) {
                    DB::rollBack();
                    throw $e;
                }
            }
            //返回签署页面的url
            if($reserve_contract->status!==Contractmodel::STATUS_ARCHIVED){
                $targetUrl    = $this->signFddUrl($reserve_contract);
            }
        }
        $this->api_res(0,[compact('targetUrl')]);
    }

    public function contractPaper($resident)
    {
        $data   = $this->test($resident);
        //1,生成合同
        $contract   = new Contractmodel();
        $contract->store_id = $resident->store_id;
        $contract->room_id  = $resident->room_id;
        $contract->resident_id  = $resident->id;
        $contract->uxid         = $resident->uxid;
        $contract->customer_id  = $resident->customer_id;
        $contract->type         = $data['type'];
        $contract->employee_id  = $resident->employee_id;
        $contract->contract_id  = $data['contract_id'];
        $contract->doc_title    = $data['doc_title'];
        $contract->download_url = $data['download_url'];
        $contract->view_url     = $data['view_url'];
        $contract->status       = $data['status'];
        $contract->rent_type    = Contractmodel::RENT_RESERVE;
        $contract->save();

        return $contract->id;
    }

    private function test($resident)
    {
        return array(
            'type'          => 'NORMAL',
            'contract_id'   => 'JINDI'.date("YmdHis").mt_rand(10,60),
            'doc_title' =>  $resident->store->abbreviation . '-' . $resident->begin_time->year .'-' . $resident->name . '-' . $resident->room_id,
            'download_url'  => 'url_download',
            'view_url'      => 'url_view',
            'status'        => Contractmodel::STATUS_ARCHIVED,
        );
    }

    /**
     * @param $resident
     * 生成预定时的账单
     */
    private function createReserveOrder($resident,$room) {
        $order  = new Ordermodel();
        $order->number       = $order->getOrderNumber();
        $order->resident_id  = $resident->id;
        $order->employee_id  = $resident->employee_id;
        $order->money        = $resident->book_money;
        $order->paid         = $resident->book_money;
        $order->year         = $resident->book_time->year;
        $order->month        = $resident->book_time->month;
        $order->remark       = $resident->remark;
        $order->room_id      = $room->id;
        $order->store_id     = $room->store_id;
        $order->room_type_id = $room->room_type_id;
        $order->status       = Ordermodel::STATE_PENDING;
        $order->deal         = Ordermodel::DEAL_UNDONE;
        $order->type         = Ordermodel::PAYTYPE_RESERVE;
        $b                   = $order->save();
        if($b){
            return $order->id;
        }else{
            return false;
        }
    }

    /**
     * 生成签署合同的页面
     * */
    public function contractFdd($resident)
    {
        //获取合同模板
        $roomtype   = $resident->roomunion->roomtype;
        $contract_template  = Contracttemplatemodel::where(['room_type_id'=>$roomtype->id,'rent_type'=>'RESERVE'])->first();
        if(ENVIRONMENT=='development'){
            //测试
            $this->fadada->uploadTemplate('http://tfunx.oss-cn-shenzhen.aliyuncs.com/'.$contract_template->contract_tpl_path,$contract_template->fdd_tpl_id);
        }
        $store  = $resident->store;
        //签署合同需要准备的信息
        $contractNumber = $resident->store->abbreviation . '-' . $resident->begin_time->year .'-' . $resident->name . '-' . $resident->room_id;
        $parameters     = array(
            'contract_number'     => $contractNumber,               //合同号
            'store_name'          => $store->name,        //+门店
            'customer_name'       => $resident->name,               //租户姓名
            'id_card'             => $resident->card_number,        //身份证号
            'phone'               => $resident->phone,              //电话号码
//            'address'             => $resident->address,            //地址
//            'alternative_person'  => $resident->alternative,        //紧急联人
//            'alternative_phone'   => $resident->alter_phone,        //紧急联系人电话
            'store_address'       => $store->provice.$store->city.$store->district.$store->address, //+门店地址
            'room_number'         => $resident->roomunion->number,       //房间号
            'year_start'          => "{$resident->begin_time->year}",           //起租年
            'month_start'         => "{$resident->begin_time->month}",          //起租月
            'day_start'           => "{$resident->begin_time->day}",            //起租日
            'year_end'            => "{$resident->end_time->year}",             //结束年
            'month_end'           => "{$resident->end_time->month}",            //结束月
            'day_end'             => "{$resident->end_time->day}",              //接速日
            'rent_money'          => "{$resident->rent_price}",            //租金
//            'rent_money_upper'    => num2rmb($resident->rent_price),       //租金确认
            'service_money'       => "{$resident->property_price}",        //服务费
            'electricity_price'   => $store->electricity_price,           //电费
            'water_price'         => $store->water_price,                  //冷水
            'hot_water_price'           => $store->hot_water_price,                  //热水
            'book_money'           => $resident->book_money,            //定金
//            'service_money_upper' => num2rmb($resident->property_price),   //服务费确认
//            'deposit_money'       => "{$resident->deposit_money}",              //暂时不确定
//            'deposit_month'       => (string)$resident->deposit_month,          //金额确定
//            'deposit_money_upper' => num2rmb($resident->deposit_money),         //金额确定
//            'tmp_deposit'         => "{$resident->tmp_deposit}",                //临时租金
//            'tmp_deposit_upper'   => num2rmb($resident->tmp_deposit),           //零食租金确认
            'special_term'        => $resident->special_term ? $resident->special_term : '无',
//            'year'                => date("Y"),                         //签约年
//            'month'               => date("m"),                         //签约月
//            'day'                 => date("d"),                         //签约日
            'attachment_2_date'   => date("Y-m-d")                      //最终时间确认
        );

        $data['name']=$resident->name;
        $data['phone']=$resident->phone;
        $data['cardNumber']=$resident->card_number;
        $data['cardType']=$resident->card_type;

        $CustomerCA= $this->getCustomerCA($data);

        $contractId   = 'JINDI'.date("YmdHis").mt_rand(10,60);

        $res2        = $this->fadada->generateContract(
            $parameters['contract_number'],
            $contract_template->fdd_tpl_id,
            $contractId,
            $parameters,
            12
        );

        $data['type']          = Contractmodel::TYPE_FDD;
        $data['customer_id']      = $CustomerCA;
        $data['download_url']    = $res2['download_url'];
        $data['view_url']       = $res2['viewpdf_url'];
        $data['status']          = Contractmodel::STATUS_GENERATED;
        $data['contract_id']      = $contractId;
        $data['doc_title'] =    $parameters['contract_number'];

        $contract   = new Contractmodel();

        //1,生成合同
        $contract->store_id = $resident->store_id;
        $contract->room_id  = $resident->room_id;
        $contract->resident_id  = $resident->id;
        $contract->uxid         = $resident->uxid;
        $contract->customer_id  = $resident->customer_id;
        $contract->fdd_customer_id  = $data['customer_id'];
        $contract->type         = $data['type'];
        $contract->employee_id  = $resident->employee_id;
        $contract->contract_id  = $data['contract_id'];
        $contract->doc_title    = $data['doc_title'];
        $contract->download_url = $data['download_url'];
        $contract->view_url     = $data['view_url'];
        $contract->status       = $data['status'];
//            $contract->sign_type       = Contractmodel::SIGN_NEW ;
        $contract->save();

        return $contract;

    }


    /**
     * fdd签署
     */
    public function signFddUrl($contract){

        $recordOld = $contract->transactions->where('role', Fddrecordmodel::ROLE_B)
            ->where('status', Fddrecordmodel::STATUS_INITIATED)->first();

        if (count($recordOld)) {
            $transactionId = $recordOld->transaction_id;
        }else{
            $transactionId  = 'B'.date("Ymd His").mt_rand(10,60);
        }

        //生成调用该接口所需要的信息

        $this->load->helper('url');
        $data2 = $this->fadada->signARequestData(
            $contract['fdd_customer_id'],
            $contract['contract_id'],
            $transactionId,
            $contract['doc_title'],
//            site_url('resident/contract/signresult'),   //return_url
            config_item('base_url').'resident/contract/signresult',
            config_item('fdd_notify_url')     //notify_url
        );

        //手动签署, 只有页面跳转到法大大平台交易才能生效, 因此, 若上一步骤失败, 就不该存储交易记录.
        if (!$recordOld) {
            $record = new Fddrecordmodel();
            $record->role = Fddrecordmodel::ROLE_B;
            $record->status = Fddrecordmodel::STATUS_INITIATED;
            $record->remark = '乙方发起了签署动作';
            $record->contract_id = $contract->id;
            $record->transaction_id = $transactionId;
            $record->save();
        }
        $baseUrl = array_shift($data2);
        $result['signurl']=$baseUrl . '?' . http_build_query($data2);
        return $result['signurl'];
    }

    /**
     * 用户签署之后跳转的页面
     * 获取签署结果, 更新合同状态为签署中
     */
    public function signResult()
    {
        $this->load->library('fadada');
        $this->load->library('form_validation');

        $config = array(
            array(
                'field' => 'transaction_id',
                'label' => 'transaction_id',
                'rules' => 'required',
            ),
            array(
                'field' => 'result_code',
                'label' => 'result_code',
                'rules' => 'required',
            ),
            array(
                'field' => 'result_desc',
                'label' => 'result_desc',
                'rules' => 'required',
            ),
            array(
                'field' => 'timestamp',
                'label' => 'timestamp',
                'rules' => 'required',
            ),
            array(
                'field' => 'msg_digest',
                'label' => 'msg_digest',
                'rules' => 'required',
            )
        );

        $input = array(
            'transaction_id' => trim($this->input->get('transaction_id', true)),
            'result_code'    => trim($this->input->get('result_code', true)),
            'result_desc'    => trim($this->input->get('result_desc', true)),
            'download_url'   => trim($this->input->get('download_url', true)),
            'viewpdf_url'    => trim($this->input->get('viewpdf_url', true)),
            'timestamp'      => trim($this->input->get('timestamp', true)),
            'msg_digest'     => trim($this->input->get('msg_digest', true)),
        );

        //CI 中的表单验证, 可能是只能验证 form 表单提交的数据, url-query 中携带的数据, 好像无法验证
        $this->form_validation->set_data($input);
        $this->form_validation->set_rules($config);

        if ($this->form_validation->run() == FALSE) {
            //redirect(site_url('center'));
            $this->api_res(1002);
            return;
        }

        //获取完参数之后进行校验
        $msgDigestData = array(
            'sha1' => [config_item('fadada_api_app_secret'), $input['transaction_id']],
            'md5'  => [$input['timestamp']],
        );

        try {
            $msgDigestStr = $this->fadada->getMsgDigest($msgDigestData);

            if ($msgDigestStr != $input['msg_digest']) {
                throw new Exception('msg_digest 验证失败');
            }

            $this->load->model('fddrecordmodel');
            $this->load->model('contractmodel');
            $this->load->model('residentmodel');
            $this->load->model('roomunionmodel');
            $this->load->model('ordermodel');
            //更新合同记录, 将合同状态设置为签署中
            $contract = Fddrecordmodel::where('transaction_id', $input['transaction_id'])->first()->contract;
            if ($contract->status == Contractmodel::STATUS_GENERATED) {
                $contract->status = Contractmodel::STATUS_SIGNING;
                $contract->save();
            }
        } catch (Exception $e) {
            log_message('error', $e->getMessage());
            //redirect(site_url('center'));
            throw $e;
        }

        $resident   = $contract->resident;
        $room   = $contract->roomunion;

        //没有问题就跳转支付页面

        header('Location:'.config_item('my_bill_url'));

        //$this->api_res(0);

        //redirect(site_url(['order', 'payment', $contract->resident->id]));
    }

}

