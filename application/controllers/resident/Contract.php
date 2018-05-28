<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as DB;
//use mikehaertl\pdftk\Pdf;
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
    }

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
     * 信息确认之后，开始签约流程
     */
//    public function confirm()
//    {
//        if(!$this->validation())
//        {
//            $field = ['resident_id','name','phone','verify_code','id_card','id_type','alternative','alter_phone','address'];
//            $this->api_res(1002,['errmsg'=>$this->form_first_error($field)]);
//            return ;
//        }
//        $residentId     = trim($this->input->post('resident_id', true));
//        $name           = trim($this->input->post('name', true));
//        $phone          = trim($this->input->post('phone', true));
//        $verifyCode     = trim($this->input->post('verify_code', true));
//        $cardNumber     = trim($this->input->post('id_card', true));
//        $cardType       = trim($this->input->post('id_type', true));
//        $alternative    = trim($this->input->post('alternative', true));
//        $alterPhone     = trim($this->input->post('alter_phone', true));
//        $address        = trim($this->input->post('address', true));
//
//        try {
//            //判断手机号码, 身份证号的合法性, 校验验证码
//            if (!isMobile($phone) || !isMobile($alterPhone)) {
//                throw new Exception('请检查手机号码!');
//            }
//
//            if (Residentmodel::CARD_ZERO == $cardType AND !isCardno($cardNumber)) {
//                throw new Exception('请检查证件号码!');
//            }
//
//            $this->checkVerifyCode($phone, $verifyCode);
//            $resident   = Residentmodel::findOrFail($residentId);
//
//            if ($resident->customer->openid != $this->auth->id()) {
//                throw new Exception('没有操作权限');
//            }
//
//            //更新一下住户的信息
//            $resident->update([
//                'name'        => $name,
//                'phone'       => $phone,
//                'card_type'   => $cardType,
//                'card_number' => $cardNumber,
//                'address'     => $address,
//                'alternative' => $alternative,
//                'alter_phone' => $alterPhone,
//            ]);
//
//            $targetUrl      = site_url(['order', 'payment', $residentId]);
//            $contract       = $resident->contract;
//            $contractType   = $resident->room->apartment->contract_type;
//
//            //如果纸质合同, 就走纸质合同流程
//            if (Storemodel::C_TYPE_NORMAL == $contractType) {
//                if (empty($contract)) {
//                    //生成纸质版合同的东东
//                    $this->generate($resident, ['type' => Contractmodel::TYPE_NORMAL]);
//
//                    $orderUnpaidCount   = $resident->orders()
//                        ->whereIn('status', [Ordermodel::STATE_AUDITED, Ordermodel::STATE_PENDING, Ordermodel::STATE_CONFIRM])
//                        ->count();
//
//                    if (0 == $orderUnpaidCount) {
//                        $resident->update(['status' => Residentmodel::STATE_NORMAL]);
//                        $resident->room->update(['status' => Roommodel::STATE_RENT]);
//                    }
//                }
//            } else {
//                //没有合同, 就先申请证书, 然后生成合同
//                if (empty($contract)) {
//                    //申请证书
//                    $customerCA = $this->getCustomerCA(compact('name', 'phone', 'cardNumber', 'cardType'));
//                    //生成合同
//                    $contract   = $this->generate($resident, [
//                        'type'   => Contractmodel::TYPE_FDD,
//                        'uxid'   => $customerCA,
//                    ]);
//                }
//                //合同没归档就去签署页面
//                if (Contractmodel::STATUS_ARCHIVED != $contract->status) {
//                    $targetUrl = $this->getSignUrl($contract);
//                }
//            }
//        } catch (Exception $e){
//            log_message('error', $e->getMessage());
//            throw new $e;
//        }
//      //  Util::success('请求成功!', compact('targetUrl'));
//        $this->api_res(0,['targetUrl'=>$targetUrl]);
//    }

    /**
     * 生成租房合同, 有两种
     * 1. 法大大电子合同
     * 2. 不走法大大流程 生成合同电子版, 便于打印
     * 这里还要根据实际情况做响应的更改
     */
//    private function generate($resident, array $data)
//    {
//        $room       = $resident->room;
//        $apartment  = $resident->room->apartment;
//        $rentType   = $resident->rent_type;
//
//        //统计本公寓今年的合同的数量
//        $contractCount  = $apartment->contracts()
//            ->where('created_at', '>=', $resident->begin_time->startOfYear())
//            ->count();
//
//        //生成该合同的编号
//        $contractNumber = $apartment->contract_number_prefix . '-' . $resident->begin_time->year . '-' . sprintf("%03d", ++ $contractCount) . '-' . $resident->name . '-' . $room->number;
//
//        //确定合同结束的时间
//        $now            = Carbon::now();
//
//        //生成电子合同, 这个所有的整数都转换成了字符串类型, 否则调用接口会出错
//        $parameters     = array(
//            'contract_number'     => $contractNumber,
//            'customer_name'       => $resident->name,
//            'id_card'             => $resident->card_number,
//            'phone'               => $resident->phone,
//            'address'             => $resident->address,
//            'alternative_person'  => $resident->alternative,
//            'alternative_phone'   => $resident->alter_phone,
//            'room_number'         => $resident->room->number,
//            'year_start'          => "{$resident->begin_time->year}",
//            'month_start'         => "{$resident->begin_time->month}",
//            'day_start'           => "{$resident->begin_time->day}",
//            'year_end'            => "{$resident->end_time->year}",
//            'month_end'           => "{$resident->end_time->month}",
//            'day_end'             => "{$resident->end_time->day}",
//            'rent_money'          => "{$resident->real_rent_money}",
//            'rent_money_upper'    => num2rmb($resident->real_rent_money),
//            'service_money'       => "{$resident->real_property_costs}",
//            'service_money_upper' => num2rmb($resident->real_property_costs),
//            'deposit_money'       => "{$resident->deposit_money}",
//            'deposit_month'       => (string)$resident->deposit_month,
//            'deposit_money_upper' => num2rmb($resident->deposit_money),
//            'tmp_deposit'         => "{$resident->tmp_deposit}",
//            'tmp_deposit_upper'   => num2rmb($resident->tmp_deposit),
//            'special_term'        => $resident->special_term ? $resident->special_term : '无',
//            'year'                => "{$now->year}",
//            'month'               => "{$now->month}",
//            'day'                 => "{$now->day}",
//            'attachment_2_date'   => $now->format('Y-m-d'),
//        );
//
//        //如果是短租, 单日价格是(房租原价*1.2/30 + 物业费/30)
//        if (Residentmodel::RENTTYPE_SHORT == $rentType) {
//            $shortDayPrice                      = ceil($room->rent_money * 1.2 / 30 + $resident->real_property_costs / 30);
//            $parameters['short_rent_price']     = "{$shortDayPrice}";
//            $parameters['short_price_upper']    = num2rmb($parameters['short_rent_price']);
//        }
//
//        //看是否需要走法大大的流程, 生成不同的合同
//        $contractId             = 'JINDI'.date("YmdHis").mt_rand(10,60);
//        $contract               = new Contractmodel();
//        $contract->doc_title    = $parameters['contract_number'];
//        $contract->contract_id  = $contractId;
//
//        if (Contractmodel::TYPE_FDD == $data['type']) {
//            if (!isset($room->roomtype->fdd_tpl_id[$rentType])) {
//                throw new Exception('未找到相应的合同模板, 请稍后重试!');
//            }
//
//            $docTitle   = $parameters['contract_number'];
//            $templateId = $apartment->fdd_customer_id;
//            $res        = $this->fadada->generateContract(
//                $parameters['contract_number'],
//                $room->roomtype->fdd_tpl_id[$rentType],
//                $contractId,
//                $parameters,
//                12
//            );
//            if (false == $res) {
//                throw new Exception($this->fadada->showError());
//            }
//            $contract->type             = Contractmodel::TYPE_FDD;
//            $contract->customer_id      = $data['customer_id'];
//            $contract->download_url     = $res['download_url'];
//            $contract->view_url         = $res['viewpdf_url'];
//            $contract->status           = Contractmodel::STATUS_GENERATED;
//        } else {
//            if (!isset($room->roomtype->contract_tpl_path[$rentType]['path'])) {
//                throw new Exception('合同模板不存在, 请稍后重试');
//            }
//
//            //用自己的方法生成合同
//            $outputFileName = "{$resident->id}.pdf";
//            $outputDir      = "contract/{$room->roomtype->id}/";
//            $templatePath   = $room->roomtype->contract_tpl_path[$rentType]['path'];
//
//            if (!file_exists($templatePath)) {
//                throw new Exception('合同模板不存在, 请稍后重试!');
//            }
//
//            if (!is_dir($outputDir)) {
//                if (!mkdir(FCPATH.$outputDir, 0777)) {
//                    throw new Exception('无法创建目录, 请稍后重试');
//                }
//            }
//
//            $pdf = new Pdf($templatePath);
//            $pdf->fillForm($parameters)
//                ->needAppearances()
//                ->saveAs(FCPATH . $outputDir . $outputFileName);
//
//            $contract->type         = Contractmodel::TYPE_NORMAL;
//            $contract->download_url = site_url($outputDir.$outputFileName);
//            $contract->view_url     = site_url($outputDir.$outputFileName);
//            $contract->status       = Contractmodel::STATUS_ARCHIVED;
//        }
//
//        $contract->city_id      = $apartment->city->id;
//        $contract->apartment_id = $apartment->id;
//        $contract->resident_id  = $resident->id;
//        $contract->contract_id  = $contractId;
//        $contract->room_id      = $room->id;
//        $contract->save();
//
//        return $contract;
//    }

//    /**
//     * 申请用户证书
//     */
//    private function getCustomerCA($data)
//    {
//        $res = $this->fadada->getCustomerCA($data['name'], $data['phone'], $data['cardNumber'], $data['cardType']);
//
//        if ($res == false) {
//            throw new Exception($this->fadada->showError());
//        }
//
//        return $res['customer_id'];
//    }

    /**
     * 奔向签署合同页面的链接
     */
    public function getSignUrl($contract)
    {
        //查找用户之前是否有未签署的请求
        $recordOld = $contract->transactions->where('role', Fddrecordmodel::ROLE_B)
            ->where('status', Fddrecordmodel::STATUS_INITIATED)->first();

        if (count($recordOld)) {
            $transactionId = $recordOld->transaction_id;
        } else {
            $transactionId  = 'B'.date("YmdHis").mt_rand(10, 60);
        }

        //生成调用该接口所需要的信息
        $data = $this->fadada->signARequestData(
            $contract->customer_id,
            $contract->contract_id,
            $transactionId,
            $contract->doc_title,
            site_url('contract/signresult'),    //return_url
            employee_url('contract/notify')     //notify_url
        );

        if (!$data) {
            throw new Exception($this->fadada->showError());
        }

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

        $baseUrl = array_shift($data);

        return $baseUrl . '?' . http_build_query($data);
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
            redirect(site_url('center'));
        }

        //获取完参数之后进行校验
        $msgDigestData = array(
            'sha1' => [FADADA_API_APP_SECRET, $input['transaction_id']],
            'md5'  => [$input['timestamp']],
        );

        try {
            $msgDigestStr = $this->fadada->getMsgDigest($msgDigestData);

            if ($msgDigestStr != $input['msg_digest']) {
                throw new Exception('msg_digest 验证失败');
            }

            //更新合同记录, 将合同状态设置为签署中
            $contract = Fddrecordmodel::where('transaction_id', $input['transaction_id'])->first()->contract;
            if ($contract->status == Contractmodel::STATUS_GENERATED) {
                $contract->status = Contractmodel::STATUS_SIGNING;
                $contract->save();
            }
        } catch (Exception $e) {
            log_message('error', $e->getMessage());
            redirect(site_url('center'));
        }

        //没有问题就跳转支付页面
        redirect(site_url(['order', 'payment', $contract->resident->id]));
    }

    /**
     * 校验验证码
     */
    private function checkVerifyCode($phone = '', $verifyCode = '')
    {
        $key = config_item('verify_code_prefix').$this->auth->id();

        if (!isset($_SESSION[$key])) {
            throw new Exception('验证码不正确!');
        }

        $data = unserialize($_SESSION[$key]);

        if ($data['phone'] != $phone || $data['code'] != $verifyCode) {
            throw new Exception('手机号码与验证码不匹配!');
        }

        //验证完成后销毁 session
        unset($_SESSION[$key]);

        return true;
    }

    /**
     * 表单验证规则
     */
    private function validation()
    {
        $this->load->library('form_validation');
        $config = array(
            array(
                'field' => 'resident_id',
                'label' => '用户id',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'name',
                'label' => '用户名',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'phone',
                'label' => '手机',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'verify_code',
                'label' => '二维码',
                'rules' => 'trim|required',
            ),//id_card
            array(
                'field' => 'id_card',
                'label' => '证件',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'id_type',
                'label' => '类型',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'alternative',
                'label' => '紧急联系人的姓名',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'alter_phone',
                'label' => '紧急联系人电话',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'address',
                'label' => '地址',
                'rules' => 'trim|required',
            ),

        );
        $this->form_validation->set_rules($config)->set_error_delimiters('','');
        return $this->form_validation->run();
    }


    /*********************************以下为重写的内容，上面是原来的内容****************************************/
    /**
     * 合同确认页面-发送短信验证码
     */
    public function sendSms(){

        $phone        = trim(strip_tags($this->input->post('phone')));
        $resident_id  = intval(strip_tags($this->input->post('resident_id')));
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
//        $this->checkUser($resident->uxid);
        $this->load->model('roomunionmodel');
        $room   = $resident->roomunion;
        if($room->status!=Roomunionmodel::STATE_OCCUPIED){
            $this->api_res(10014);
            return;
        }
        $this->load->library('m_redis');
        if(!$this->m_redis->ttlResidentPhoneCode($phone))
        {
            $this->api_res(10007);
            return;
        }
        $this->load->library('sms');
        $code   = str_pad(rand(1,9999),4,0,STR_PAD_LEFT);
        $str    = SMSTEXT.$code;
        $this->m_redis->storeResidentPhoneCode($phone,$code);
        $this->sms->send($str,$phone);
        $this->api_res(0);
    }

    /**
     * 合同确认页面-确认签约
     */
    public  function confirm(){

        $input  = $this->input->post(null,true);
        //验证短信验证码
        //$this->load->library('m_redis');
        $resident_id    = intval(strip_tags($input['resident_id']));
        $phone    = trim(strip_tags($input['phone']));
//        if(!$this->m_redis->verifyResidentPhoneCode($input['phone'],$input['code'])){
//            $this->api_res(10007);
//            return;
//        }
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
//        $this->checkUser($resident->uxid);
        $this->load->model('roomunionmodel');
        $room   = $resident->roomunion;
        if($room->status!=Roomunionmodel::STATE_OCCUPIED){
            $this->api_res(10014);
            return;
        }
//      判断住户合同是否已经归档，有已经归档的合同 就结束
        $this->load->model('contractmodel');
        $has_contract = $resident->contract()->where('status', Contractmodel::STATUS_ARCHIVED);
//      $has_contract = $resident->contract();
        if ($has_contract->exists()) {
            $this->api_res(10015);
            return;
        }

        //判断门店的合同类型选择调用哪个合同流程
        $this->load->model('storemodel');
        $contract   = $resident->contract;
        $contract_type  = $room->store->contract_type;

        //测试使用
//        $data   = $this->generate($resident,$contract_type);
        if(Storemodel::C_TYPE_NORMAL==$contract_type){
            if(empty($contract)){
                //生成纸质版合同
                $data   = $this->generate($resident, ['type' => Contractmodel::TYPE_NORMAL]);
//                $orderUnpaidCount   = $resident->orders()
//                    ->whereIn('status', [Ordermodel::STATE_AUDITED, Ordermodel::STATE_PENDING, Ordermodel::STATE_CONFIRM])
//                    ->count();
//
//                if (0 == $orderUnpaidCount) {
//                    $resident->update(['status' => Residentmodel::STATE_NORMAL]);
//                    $resident->room->update(['status' => Roommodel::STATE_RENT]);
//                    $this->api_res(0);
//                    return;
//                }
            }else{
                $this->api_res(10016);
                return;
            }
        }else{
            if(empty($contract)){
                //申请证书
                $name       = $resident->name;
                $phone      = $resident->phone;
                $cardNumber = $resident->card_number;
                $cardType   = $resident->card_type;
                $customerCA = $this->getCustomerCA(compact('name', 'phone', 'cardNumber', 'cardType'));
                //生成法大大合同
                $data=$this->generate($resident, [
                    'type' => Contractmodel::TYPE_FDD,
                    'customer_id'   => $customerCA,
                    ]);
            }else{
                $this->api_res(10016);
                return;
            }
            //合同没归档就去签署页面
//            if (Contractmodel::STATUS_ARCHIVED != $contract->status) {
//                //$targetUrl = $this->getSignUrl($contract);
//                $this->api_res(10016);
//                return;
//            }
        }

        $contract   = new Contractmodel();
        //开始签约
        try{
            DB::beginTransaction();
            //1,生成合同
            $contract->store_id = $resident->store_id;
            $contract->room_id  = $resident->room_id;
            $contract->resident_id  = $resident->id;
            $contract->uxid  = $resident->uxid;
            $contract->customer_id  = $resident->customer_id;
            $contract->type = $data['type'];
            $contract->contract_id = $data['contract_id'];
            $contract->doc_title = $data['doc_title'];
            $contract->download_url = $data['download_url'];
            $contract->view_url = $data['view_url'];
            $contract->status = $data['status'];
            $a  = $contract->save();
            //2.生成订单
            $this->load->model('ordermodel');
            $b  = $this->ordermodel->firstCheckInOrders($resident, $room);

            if($a && $b){
                DB::commit();
            }else{
                DB::rollBack();
                $this->api_res(1009);
                return;
            }
            $this->api_res(0,['resident_id'=>$resident->id]);
        }catch (Exception $e){
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * @param $resident
     * @return array
     * 生成合同
     */
    private function generate($resident,$type)
    {
        //合同里的一个公共调用的方法
        //生成合同之后 返回这些数据 data 只返回这些数据，不保存数据库
        //参考旧版本的逻辑

        //生成该合同的编号
        $room = $resident->room;
        $apartment = $resident->room->apartment;
        $rentType = $resident->rent_type;

        //统计本公寓今年的合同的数量
        $contractCount = $apartment->contracts()
            ->where('created_at', '>=', $resident->begin_time->startOfYear())
            ->count();

        $contractNumber = $apartment->contract_number_prefix . '-' . $resident->begin_time->year . '-' .
            sprintf("%03d", ++$contractCount) . '-' . $resident->name . '-' . $room->number;

        $parameters = array(
            'contract_number' => $contractNumber,


        );
        //如果是短租, 单日价格是(房租原价*1.2/30 + 物业费/30)

        if (Residentmodel::RENTTYPE_SHORT == $rentType) {
            $shortDayPrice = ceil($room->rent_money * 1.2 / 30 + $resident->real_property_costs / 30);
            $parameters['short_rent_price'] = "{$shortDayPrice}";
            $parameters['short_price_upper'] = num2rmb($parameters['short_rent_price']);
        }

        $contractId = 'JINDI' . date("YmdHis") . mt_rand(10, 60);

        $contract = new Contractmodel();
        $contract->doc_title = $parameters['contract_number'];

//        $contract->contract_id = $contractId;

        if (Contractmodel::TYPE_FDD == $type['type']) {

            $docTitle = $parameters['contract_number'];

            $res = $this->fadada->generateContract(
                $parameters['contract_number'],
                $room->roomtype->fdd_tpl_id[$rentType],
                $contractId,
                $parameters,
                12
            );

            if (false == $res) {
                throw new Exception($this->fadada->showError());
            }

            $contract->type = Contractmodel::TYPE_FDD;
            //$contract->customer_id = $type['customer_id'];
            $contract->doctitle  = $docTitle;
            $contract->download_url = $res['download_url'];
            $contract->view_url = $res['viewpdf_url'];
            $contract->status = Contractmodel::STATUS_GENERATED;

//            return array(
//                'type' => 'FDD',
//                'contract_id' => 'JINDI123456789',
//                'doc_title' => "title",
//                'download_url' => 'url_download',
//                'view_url' => 'url_view',
//                'status' => Contractmodel::STATUS_GENERATED,
//            );

        }else{
            if (!isset($room->roomtype->contract_tpl_path[$rentType]['path'])) {
                throw new Exception('合同模板不存在, 请稍后重试');
            }

            //用自己的方法生成合同
            $outputFileName = "{$resident->id}.pdf";
            $outputDir      = "contract/{$room->roomtype->id}/";
            $templatePath   = $room->roomtype->contract_tpl_path[$rentType]['path'];

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

            $contract->type         = Contractmodel::TYPE_NORMAL;
            $contract->download_url = site_url($outputDir.$outputFileName);
            $contract->view_url     = site_url($outputDir.$outputFileName);
            $contract->status       = Contractmodel::STATUS_ARCHIVED;
        }
//
//        $contract->city_id      = $apartment->city->id;
//        $contract->apartment_id = $apartment->id;
//        $contract->resident_id  = $resident->id;
//        $contract->contract_id  = $contractId;
//        $contract->room_id      = $room->id;
        $contract->save();

        return $contract;
    }

    /**
     * 申请用户证书
     */
    private function getCustomerCA($data)
    {
        $res = $this->fadada->getCustomerCA($data['name'], $data['phone'], $data['cardNumber'], $data['cardType']);

        if ($res == false) {
            throw new Exception($this->fadada->showError());
        }

        return $res['customer_id'];
    }






}
