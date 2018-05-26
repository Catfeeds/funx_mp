<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/18 0018
 * Time:        17:27
 * Describe:    用户合同
 */

/**
 * 法大大电子合同相关操作
 */
class Contract extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     *合同信息确认
     *
     **/
    public function checksign($residentId)
    {

    }

    /**
     * 生成租房合同, 有两种
     * 1. 法大大电子合同
     * 2. 不走法大大流程 生成合同电子版, 便于打印
     * 这里还要根据实际情况做响应的更改
     */
    public function generate(){
        //根据房型获取用户合同模板

        //返回接口
        //签署的是电子合同：
        //1电子合同，跳转至合同签署页面
        //2纸质合同，跳转至账单页面
        $result['sign_type']=2;
        $result['sign_url']='';


        $this->api_res(0,$result);


    }

   /**
    *生成法大大合同
    *
    *
    *
    **/
   private function signFadada(){

   }

    /**
     * 申请用户证书
     **/
    private function getCustomerCA($data)
    {
        $res = $this->fadada->getCustomerCA($data['name'], $data['phone'], $data['cardNumber'], $data['cardType']);

        if ($res == false) {
            throw new Exception($this->fadada->showError());
        }

        return $res['customer_id'];
    }
    /**
     * 生成签署合同页面
     */
    private function getSignUrl($contract)
    {

    }

    /**
     * 用户签署之后跳转的页面
     * 获取签署结果, 更新合同状态为签署中
     */
    public function signResult()
    {

    }

    /**
    **生成纸质版合同
     **/
    private function signPaper(){

    }
}