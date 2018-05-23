<?php
/**
 * User: wws
 * Date: 2018-05-23
 * Time: 10:37
 */


/**
 * 法大大电子合同相关操作
 */
class Contract extends Wechat_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->library('fadada');
    }

    /**
     * 合同信息确认页面
     */
    public function preview($residentId)
    {
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

        return $this->api_res->render('contract/confirm.html.twig', compact('resident'));
    }

    /**
     * 信息确认之后，开始签约流程
     */
    public function confirm()
    {
        $residentId     = trim($this->input->post('resident_id', true));
        $name           = trim($this->input->post('name', true));
        $phone          = trim($this->input->post('phone', true));
        $verifyCode     = trim($this->input->post('verify_code', true));
        $cardNumber     = trim($this->input->post('id_card', true));
        $cardType       = trim($this->input->post('id_type', true));
        $alternative    = trim($this->input->post('alternative', true));
        $alterPhone     = trim($this->input->post('alter_phone', true));
        $address        = trim($this->input->post('address', true));


        try {
            //判断手机号码, 身份证号的合法性, 校验验证码
            if (!Util::isMobile($phone) || !Util::isMobile($alterPhone)) {
                throw new Exception('请检查手机号码!');
            }

            if (Residentmodel::CARD_ZERO == $cardType AND !Util::isCardno($cardNumber)) {
                throw new Exception('请检查证件号码!');
            }

            $this->checkVerifyCode($phone, $verifyCode);

            $resident   = Residentmodel::findOrFail($residentId);

            if ($resident->customer->openid != $this->auth->id()) {
                throw new Exception('没有操作权限');
            }

            //更新一下住户的信息
            $resident->update([
                'name'        => $name,
                'phone'       => $phone,
                'card_type'   => $cardType,
                'card_number' => $cardNumber,
                'address'     => $address,
                'alternative' => $alternative,
                'alter_phone' => $alterPhone,
            ]);

            $targetUrl      = site_url(['order', 'payment', $residentId]);
            $contract       = $resident->contract;
            $contractType   = $resident->room->apartment->contract_type;

            //如果纸质合同, 就走纸质合同流程
            if (Storemodel::C_TYPE_NORMAL == $contractType) {
                if (empty($contract)) {
                    //生成纸质版合同的东东
                    $this->generate($resident, ['type' => Contractmodel::TYPE_NORMAL]);

                    $orderUnpaidCount   = $resident->orders()
                        ->whereIn('status', [Ordermodel::STATE_AUDITED, Ordermodel::STATE_PENDING, Ordermodel::STATE_CONFIRM])
                        ->count();

                    if (0 == $orderUnpaidCount) {
                        $resident->update(['status' => Residentmodel::STATE_NORMAL]);
                        $resident->room->update(['status' => Roommodel::STATE_RENT]);
                    }
                }
            } else {
                //没有合同, 就先申请证书, 然后生成合同
                if (empty($contract)) {
                    //申请证书
                    $customerCA = $this->getCustomerCA(compact('name', 'phone', 'cardNumber', 'cardType'));
                    //生成合同
                    $contract   = $this->generate($resident, [
                        'type'          => Contractmodel::TYPE_FDD,
                        'customer_id'   => $customerCA,
                    ]);
                }
                //合同没归档就去签署页面
                if (Contractmodel::STATUS_ARCHIVED != $contract->status) {
                    $targetUrl = $this->getSignUrl($contract);
                }
            }
        } catch (Exception $e) {
            log_message('error', $e->getMessage());
            Util::error($e->getMessage());
        }

        Util::success('请求成功!', compact('targetUrl'));
    }


}
