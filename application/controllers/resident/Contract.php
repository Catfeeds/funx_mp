<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as DB;
use mikehaertl\pdftk\Pdf;

/**
 * User: wws
 * Date: 2018-05-23
 * Time: 10:37
 */
/**
 * 法大大电子合同相关操作
 */
class Contract extends MY_Controller
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
    1.身份证 2.护照 6.社会保障卡 A.武装警察身份证 B.港澳通行证 C.台湾居民来往大陆通行证 E.户口本
    F.临时身份证 P外国人永久居留证 BL.营业执照 OTHERE.其它
     **/
    /**
     * 合同的状态
     *  'GENERATED';       //合同已经生成
     * 'SIGNING';         //双方签署过程中
     * 'ARCHIVED';        //合同归档
     * 合同类型
     * 'FDD': 法大大
     * 'NORMAL’:正常合同
     *
     */
    /**
     * 合同的类型, 电子合同还是纸质合同
     */

    /**
     * 合同信息确认页面
     */
    public function preview()
    {
        $post = $this->input->post(null,true);
        $residentId =trim($post['id']);
        try {
            //用户扫描二维码后, 发送图文消息, 然后进入的页面
            $resident = Residentmodel::findOrFail($residentId);
            $customer = Customermodel::where('openid', $this->auth->id())->firstOrFail();

            //将住户信息与当前登录的微信用户关联起来, 首先进行判断
            if ($resident->uxid && $resident->uxid != $customer->id) {
                throw new Exception('未找到您的订单!');
            }
            if ($resident->uxid == 0) {
                $resident->uxid = $customer->id;
                $resident->save();
            }
            //更新订单
            $resident->orders()->update(['uxid' => $customer->id]);
            // $resident->coupons()->where('customer_id', 0)->update(['customer_id' => $customer->id]);
            // $customer->coupons()->where('resident_id', 0)->update(['resident_id' => $residentId]);

            //跳转到合同信息确认页面
            $contract = $resident->contract()->where('status', Contractmodel::STATUS_ARCHIVED);
            if ($contract->exists()) {
                throw new Exception('合同已经签署完成!');
            }
        } catch (Exception $e) {
            log_message('error', $e->getMessage());
            redirect(site_url(['order', 'status']));
        }
        $this->api_res(0,['contract'=>$contract]);
    }

    /**
     * 合同确认页面-发送短信验证码
     */
    public function sendSms(){

        $phone        = trim(strip_tags($this->input->post('phone')));            //手机号
        $resident_id  = trim(strip_tags($this->input->post('resident_id')));      //用户id
        $this->load->model('residentmodel');
        $resident   = Residentmodel::find($resident_id);                          //租户信息
        if(!$resident){
            $this->api_res(1007);
            return;
        }
        if($resident->phone!=$phone){
            $this->api_res(10010);
            return;
        }
        //验证住户的uxid是不是当前ID
//        $this->checkUser($resident->uxid);
        $this->load->model('roomunionmodel');
        $room   = $resident->roomunion;
        if($room->status!=Roomunionmodel::STATE_OCCUPIED){          //不是房间被占用的状态  //房间空着 结束
            $this->api_res(10014);
            return;
        }
        $this->load->library('m_redis');
        if(!$this->m_redis->ttlResidentPhoneCode($phone))
        {
            $this->api_res(10007);
            return;
        }
        $this->load->library('sms');                        //使用云片发送短信验证码 //STR_PAD_LEFT  0
        $code   = str_pad(rand(1,9999),4,0,STR_PAD_LEFT);
        $str    = SMSTEXT.$code;                            //SMSTEXT.$code  //SMSTEXT 指定信息
        $this->m_redis->storeResidentPhoneCode($phone,$code);
        $this->sms->send($str,$phone);
        $this->api_res(0);
    }

    /**
     * 合同确认页面-确认签约
     */
    public  function confirm(){

        $input  = $this->input->post(null,true);
        $resident_id    = intval(strip_tags($input['resident_id']));
        $phone          = trim(strip_tags($input['phone']));
//        $code           = trim(strip_tags($input['code']));
        //验证短信验证码
        $this->load->library('m_redis');
        if(!$this->m_redis->verifyResidentPhoneCode($input['phone'],$input['code'])){
            $this->api_res(10008);
            return;
        }
        $this->load->model('residentmodel');
        $resident   = Residentmodel::find($resident_id);
        if(!$resident){
            $this->api_res(1007);
            return;
        }
        if($resident->phone!=$phone){
            $this->api_res(10010);
            return;
        }
        //验证住户的uxid是不是当前ID
        //$this->checkUser($resident->uxid);
        $this->load->model('roomunionmodel');
        $room   = $resident->roomunion;
        if($room->status!=Roomunionmodel::STATE_OCCUPIED){
            $this->api_res(10014);
            return;
        }
//      判断住户合同是否已经归档，有已经归档的合同 就结束
        $this->load->model('contractmodel');
        $has_contract = $resident->contract()->where('status', Contractmodel::STATUS_ARCHIVED);
//        $has_contract = $resident->contract();
        if ($has_contract->exists()) {
            $this->api_res(10015);
            return;
        }

        //判断门店的合同类型选择调用哪个合同流程
        $this->load->model('storemodel');
        $contract   = $resident->contract;

        $contract_type  = $room->store->contract_type;


        $this->load->model('roomtypemodel');
        //默认跳转的页面 账单列表
        $targetUrl  = '';
        log_message('error', $contract_type.'||aaaaaaaaaa');
        log_message('error', $resident->card_type.'||bbbbbbbbbbbbbbb');
        if((Storemodel::C_TYPE_NORMAL==$contract_type&&$resident->card_type!=0)||(Storemodel::C_TYPE_NORMAL==$contract_type&&$resident->card_type!='IDCARD')){
            log_message('error', '纸质合同生成');
            if($resident->status!='$resident'){
                //生成纸质版合同
                log_message('error', '纸质合同生成2');
                $contract   = $this->contractPaper($resident);

                $this->load->model('ordermodel');
                $this->ordermodel->firstCheckInOrders($resident, $room);

                $orderUnpaidCount   = $resident->orders()
                    ->whereIn('status', [Ordermodel::STATE_AUDITED, Ordermodel::STATE_PENDING, Ordermodel::STATE_CONFIRM])
                    ->count();

                if (0 == $orderUnpaidCount) {
                    $resident->update(['status' => Residentmodel::STATE_NORMAL]);
                    $resident->roomunion->update(['status' => Roomunionmodel::STATE_RENT]);
                }
            }
        }else{

            $this->load->model('fddrecordmodel');
            if(empty($contract)){

                $contract   = $this->signContract($resident);

                $this->load->model('ordermodel');
                $this->ordermodel->firstCheckInOrders($resident, $room);

            }

            if($contract->status!==Contractmodel::STATUS_ARCHIVED){

                $targetUrl    = $this->signFddUrl($contract);

            }
        }


        $this->api_res(0,[compact('targetUrl')]);

    }

    /**
     * 生成签署合同的页面
     * */
    public function signContract($resident)
    {
        //获取合同模板
        $roomtype   = $resident->roomunion->roomtype;
        $contract_template  = Contracttemplatemodel::where(['room_type_id'=>$roomtype->id,'rent_type'=>$resident->rent_type])->first();
        if(ENVIRONMENT=='development'){
            //测试
            $this->fadada->uploadTemplate('http://tfunx.oss-cn-shenzhen.aliyuncs.com/'.$contract_template->contract_tpl_path,$contract_template->fdd_tpl_id);
        }
        //签署合同需要准备的信息
        $contractNumber = $resident->store->abbreviation . '-' . $resident->begin_time->year .'-' . $resident->name . '-' . $resident->room_id;
        $parameters     = array(
            'contract_number'     => $contractNumber,               //合同号
            'customer_name'       => $resident->name,               //租户姓名
            'id_card'             => $resident->card_number,        //身份证号
            'phone'               => $resident->phone,              //电话号码
            'address'             => $resident->address,            //地址
            'alternative_person'  => $resident->alternative,        //紧急联人
            'alternative_phone'   => $resident->alter_phone,        //紧急联系人电话
            'room_number'         => $resident->roomunion->number,       //房间号
            'year_start'          => "{$resident->begin_time->year}",           //起租年
            'month_start'         => "{$resident->begin_time->month}",          //起租月
            'day_start'           => "{$resident->begin_time->day}",            //起租日
            'year_end'            => "{$resident->end_time->year}",             //结束年
            'month_end'           => "{$resident->end_time->month}",            //结束月
            'day_end'             => "{$resident->end_time->day}",              //接速日
            'rent_money'          => "{$resident->real_rent_money}",            //租金
            'rent_money_upper'    => num2rmb($resident->real_rent_money),       //租金确认
            'service_money'       => "{$resident->real_property_costs}",        //服务费
            'service_money_upper' => num2rmb($resident->real_property_costs),   //服务费确认
            'deposit_money'       => "{$resident->deposit_money}",              //暂时不确定
            'deposit_month'       => (string)$resident->deposit_month,          //金额确定
            'deposit_money_upper' => num2rmb($resident->deposit_money),         //金额确定
            'tmp_deposit'         => "{$resident->tmp_deposit}",                //临时租金
            'tmp_deposit_upper'   => num2rmb($resident->tmp_deposit),           //零食租金确认
            'special_term'        => $resident->special_term ? $resident->special_term : '无',
            'year'                => date("Y"),                         //签约年
            'month'               => date("m"),                         //签约月
            'day'                 => date("d"),                         //签约日
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

    private function test()
    {
        return array(
            'type' => 'NORMAL',
            'contract_id' => 'JINDI'.date("YmdHis").mt_rand(10,60),
            'doc_title' => "title",
            'download_url' => 'url_download',
            'view_url' => 'url_view',
            'status' => Contractmodel::STATUS_ARCHIVED,
            //'customer_id' => null,
        );
    }

    /**
     * 申请用户证书
     */
    private function getCustomerCA($data)
    {
        $res = $this->fadada->getCustomerCA($data['name'], $data['phone'], $data['cardNumber'], $data[' cardType']);
        if ($res == false) {
            throw new Exception($this->fadada->showError());
        }

        return $res['customer_id'];
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
            $transactionId  = 'B'.date("Ymd His").mt_rand(10, 60);
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



    /**
     * 生成纸质版合同
     */
    public function contractPaper($resident)
    {


       // $data   = $this->uploadContractPaper($resident);


        $data   = $this->test();

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
//            $contract->sign_type       = Contractmodel::SIGN_NEW ;
        $contract->save();

        return $contract;
    }

    /**
     * 生成上传纸质合同
     */
    private function uploadContractPaper($resident){

        //获取合同模板
        $room       = $resident->roomunion;
        $rentType   = $resident->rent_type;
        $roomtype   = $resident->roomunion->roomtype;

        $contract_template  = Contracttemplatemodel::where(['room_type_id'=>$roomtype->id,'rent_type'=>$resident->rent_type])->first();

        $contractId             = 'JINDI'.date("YmdHis").mt_rand(10,60);

        //签署合同需要准备的信息
        $contractNumber = $resident->store->abbreviation . '-' . $resident->begin_time->year .'-' . $resident->name . '-' . $resident->room_id;
        $parameters     = array(
            'contract_number'     => $contractNumber,               //合同号
            'customer_name'       => $resident->name,               //租户姓名
            'id_card'             => $resident->card_number,        //身份证号
            'phone'               => $resident->phone,              //电话号码
            'address'             => $resident->address,            //地址
            'alternative_person'  => $resident->alternative,        //紧急联人
            'alternative_phone'   => $resident->alter_phone,        //紧急联系人电话
            'room_number'         => $resident->room->number,       //房间号
            'year_start'          => "{$resident->begin_time->year}",           //起租年
            'month_start'         => "{$resident->begin_time->month}",          //起租月
            'day_start'           => "{$resident->begin_time->day}",            //起租日
            'year_end'            => "{$resident->end_time->year}",             //结束年
            'month_end'           => "{$resident->end_time->month}",            //结束月
            'day_end'             => "{$resident->end_time->day}",              //接速日
            'rent_money'          => "{$resident->real_rent_money}",            //租金
            'rent_money_upper'    => num2rmb($resident->real_rent_money),       //租金确认
            'service_money'       => "{$resident->real_property_costs}",        //服务费
            'service_money_upper' => num2rmb($resident->real_property_costs),   //服务费确认
            'deposit_money'       => "{$resident->deposit_money}",              //暂时不确定
            'deposit_month'       => (string)$resident->deposit_month,          //金额确定
            'deposit_money_upper' => num2rmb($resident->deposit_money),         //金额确定
            'tmp_deposit'         => "{$resident->tmp_deposit}",                //临时租金
            'tmp_deposit_upper'   => num2rmb($resident->tmp_deposit),           //零食租金确认
            'special_term'        => $resident->special_term ? $resident->special_term : '无',
            'year'                => date("Y"),                         //签约年
            'month'               => date("m"),                         //签约月
            'day'                 => date("d"),                         //签约日
            'attachment_2_date'   => date("Y-m-d")                      //最终时间确认
        );


        $outputFileName = "{$resident->id}.pdf";
        $outputDir      = "contract/{$room->roomtype->id}/";
        $templatePath   = $this->fullAliossUrl($contract_template->contract_tpl_path);

        //$temp= file_get_contents($templatePath);


        if (!file_exists($templatePath)) {
            throw new Exception('合同模板不存在, 请稍后重试!');
        }

        if (!is_dir($outputDir)) {
            if (!mkdir(FCPATH.$outputDir, 0777)) {
                throw new Exception('无法创建目录, 请稍后重试');
            }
        }

        $pdf = new Pdf($templatePath);
        $pdf->fillForm($parameters)
            ->needAppearances()
            ->saveAs(FCPATH . $outputDir . $outputFileName);

        $data['type']            = Contractmodel::TYPE_NORMAL;
        $data['download_url']    = site_url($outputDir.$outputFileName);
        $data['view_url']        = site_url($outputDir.$outputFileName);
        $data['status']          = Contractmodel::STATUS_ARCHIVED;
        $data['contract_id']     = $contractId;
        $data['doc_title']       = $parameters['contract_number'];

        return $data;


    }

    /**
     * 查看合同
     */
    public function watchContract()
    {
        $this->load->model('storemodel');
        $this->load->model('roomunionmodel');
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
