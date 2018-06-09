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
       // $this->load->model('roomtypemodel');
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
     * 生成签署合同的页面
     * */
        public function signContract(){
            //获取合同模板
            $cont_template = Contracttemplatemodel::where(['room_type_id'=>53,'rent_type'=>'LONG'])->first();
            //签署合同需要准备的信息
//            $parameters     = array(
//                'contract_number'     => $contractNumber,               //合同号
//                'customer_name'       => $resident->name,               //租户姓名
//                'id_card'             => $resident->card_number,        //身份证号
//                'phone'               => $resident->phone,              //电话号码
//                'address'             => $resident->address,            //地址
//                'alternative_person'  => $resident->alternative,        //紧急联人
//                'alternative_phone'   => $resident->alter_phone,        //紧急联系人电话
//                'room_number'         => $resident->room->number,       //房间号
//                'year_start'          => "{$resident->begin_time->year}",    //起租年
//                'month_start'         => "{$resident->begin_time->month}",     //起租月
//                'day_start'           => "{$resident->begin_time->day}",        //起租日
//                'year_end'            => "{$resident->end_time->year}",         //结束年
//                'month_end'           => "{$resident->end_time->month}",        //结束月
//                'day_end'             => "{$resident->end_time->day}",           //接速日
//                'rent_money'          => "{$resident->real_rent_money}",           //租金
//                'rent_money_upper'    => Util::num2rmb($resident->real_rent_money),  //租金确认
//                'service_money'       => "{$resident->real_property_costs}",        //服务费
//                'service_money_upper' => Util::num2rmb($resident->real_property_costs),// 服务费确认
//                'deposit_money'       => "{$resident->deposit_money}",                   //暂时不确定
//                'deposit_month'       => (string)$resident->deposit_month,               //金额确定
//                'deposit_money_upper' => Util::num2rmb($resident->deposit_money),         //金额确定
//                'tmp_deposit'         => "{$resident->tmp_deposit}",                       //临时租金
//                'tmp_deposit_upper'   => Util::num2rmb($resident->tmp_deposit),             //零食租金确认
//                'special_term'        => $resident->special_term ? $resident->special_term : '无',  //
//                'year'                => "{$now->year}",                                    //签约年
//                'month'               => "{$now->month}",                                   //签约月
//                'day'                 => "{$now->day}",                                     //签约日
//                'attachment_2_date'   => $now->format('Y-m-d'),                             //最终时间确认
//            );
            $parameters     = array(
                'contract_number'     => '2018-06-09-001',               //合同号
                'customer_name'       => '杜伟',               //租户姓名
                'id_card'             => '511325198704153015',        //身份证号
                'phone'               => '15771763360',              //电话号码
                'address'             => 'test',            //地址
                'alternative_person'  => 'test',        //紧急联人
                'alternative_phone'   => '15555555555',        //紧急联系人电话
                'room_number'         => '2018',       //房间号
                'year_start'          => "2018",    //起租年
                'month_start'         => "06",     //起租月
                'day_start'           => "09",        //起租日
                'year_end'            => "2018",         //结束年
                'month_end'           => "07",        //结束月
                'day_end'             => "12",           //接速日
                'rent_money'          => "200",           //租金
                'rent_money_upper'    => num2rmb(200),  //租金确认
                'service_money'       => "300",        //服务费
                'service_money_upper' => num2rmb(300),// 服务费确认
                'deposit_money'       => "400",                   //押金
                'deposit_month'       => "2",               //押金月份
                'deposit_money_upper' => num2rmb(400),         //金额确定
                'tmp_deposit'         => "100",                       //其它押金
                'tmp_deposit_upper'   => num2rmb(100),             //其它押金
                'special_term'        => '无',  //
                'year'                => date("Y"),                                    //签约年
                'month'               => date("m"),                                   //签约月
                'day'                 => date("d"),                                     //签约日
                'attachment_2_date'   => date("Y-m-d")                             //最终时间确认
            );

            $data['name']='杜伟';
            $data['phone']='15771763360';
            $data['cardNumber']='511325198704153015';
            $data['cardType']='1';

            $customerCA = $this->getCustomerCA($data);


            $contractId             = 'JINDI'.date("YmdHis").mt_rand(10,60);
            $res        = $this->fadada->generateContract(
                $parameters['contract_number'],
                $cont_template->fdd_tpl_id,
                $contractId,
                $parameters,
                12
            );

            $contract['type']          = 'FDD';
            $contract['customer_id']      = $contractId;
            $contract['download_url']    = $res['download_url'];
            $contract['view_url']       = $res['viewpdf_url'];
            $contract['status']          = 'GENERATED';
            $this->api_res(0,$contract);
        }
        //生成普通的电子合同
        private function generate(){

        }

        //生成法大大的电子合同
        private function generateFDD(){
            //生成电子合同, 这个所有的整数都转换成了字符串类型, 否则调用接口会出错





        }

    /**
     * 申请用户证书
     */
    private function getCustomerCA($data)
    {
        $res = $this->fadada->getCustomerCA($data['name'], $data['phone'], $data['cardNumber'], $data['cardType']);

        if ($res == false) {
            echo "aa";
            throw new Exception($this->fadada->showError());
        }

        return $res['customer_id'];
    }





}
