<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/18 0018
 * Time:        14:14
 * Describe:    调用法大大
 */
use GuzzleHttp\Client;
require_once dirname(__FILE__) .'/Crypt3des.php';

/**
 * 法大大电子合同接口
 */
class Fadada
{
    /**
     * 出现的错误
     */
    protected $error;

    public function __construct()
    {
        $this->error = '';
    }

    /**
     * 返回出现的错误
     */
    public function showError()
    {
        return $this->error;
    }

    /**
     * 个人 CA 申请接口
     * 调用成功, 返回信息中会返回注册后的 customer_id
     * 重复调用不会多次申请
     */
    public function getCustomerCA($name, $phone, $id_card, $id_type = 0, $email = '')
    {
        try {                                         //身份证号|手机号码
            $id_mobile  = (new Crypt3des())::encrypt($id_card . '|' . $phone, config_item('fadada_api_app_secret'));
            $url        = $this->getApiUrl('syncPerson_auto.api');
            $msgDigest  = array(
                'md5'  => ['timestamp' => date('YmdHis'),],                  //请求时间
                'sha1' => [config_item('fadada_api_app_secret')]                     //秘钥
            );
            $reqData    = array(
                'customer_name' => $name,
                'ident_type'    => $id_type,
                'id_mobile'     => $id_mobile,
            );
            $res = $this->requestFdd($url, $reqData, $msgDigest);
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }

        return $res;
    }

    /**
     * 文档传输接口, 这个接口用于传输已经拟定好的合同
     * 文件源有两种方式, 一种是文件流, 一种是使用文件的url
     * 在这里使用文件url的方式
     */
    public function uploadDocs()
    {
        $url = $this->getApiUrl('uploaddocs.api');
    }

    /**
     * 法大大合同归档接口
     */
    public function contractFiling($contractId)
    {
        try {
            $url        = $this->getApiUrl('contractFiling.api');
            $reqData    = ['contract_id' => $contractId];
            $msgDigest  = array(
                'sha1' => [config_item('fadada_api_app_secret'), $contractId],
                'md5'  => ['timestamp' => date('YmdHis')],
            );
            $res = $this->requestFdd($url, $reqData, $msgDigest);
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
        return $res;
    }

    /**
     * 合同模板传输接口
     * 用于传输模板, 签署合同的时候, 基于模板生成相应的合同
     */
    public function uploadTemplate($docUrl,  $templateId)
    {
        try {
            $url        = $this->getApiUrl('uploadtemplate.api');
            $reqData    = array(
                'doc_url'     => $docUrl,
                'template_id' => $templateId,
            );
            $msgDigest  = array(
                'md5'  => ['timestamp' => date('YmdHis')],
                'sha1' => [config_item('fadada_api_app_secret'), $templateId],
            );
            $res = $this->requestFdd($url, $reqData, $msgDigest);
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
        return $res;
    }

    /**
     * 合同生成接口
     * 根据之前上传的合同模板生成合同
     */
    public function generateContract($docTitle, $templateId, $contractId, array $parameters, $fontSize = 9)
    {
        try {
            $url          = $this->getApiUrl('generate_contract.api');   //生成各个接口的具体URL
            $parameterMap = json_encode($parameters);
            $reqData      = array(
                'doc_title'     => $docTitle,
                'template_id'   => $templateId,
                'contract_id'   => $contractId,
                'parameter_map' => json_encode($parameters),
                'font_size'     => $fontSize,
            );
            $msgDigest  = array(
                'sha1'  => [config_item('fadada_api_app_secret'), $templateId, $contractId],
                'md5'   => ['timestamp' => date('YmdHis')],
                'other' => $parameterMap,
            );
            $res = $this->requestFdd($url, $reqData, $msgDigest);           //向法大大系统发送请求
        } catch (Exception $e) {
            throw $e;
        }
        return $res;
    }

    /**
     * 文档签署接口(手动签), get
     * 该接口为页面接口, 应该在客户端调用, 这里应该返回该接口所需要的 msg_digest
     * 如有需要, 同时返回该接口的url
     */
    public function extsign()
    {
        $url = $this->getApiUrl('extsign.api');
    }

    /**
     * 返回调用手动签署接口所需数据
     */
    public function signARequestData($customerId, $contractId, $transactionId, $docTitle, $returnUrl, $notifyUrl)
    {
        try {
            $msgDigest  = array(
                'sha1' => [config_item('fadada_api_app_secret'), $customerId],
                'md5'  => [
                    'transaction_id' => $transactionId,
                    'timestamp'      => date('YmdHis'),
                ],
            );
            $data = array(
                'url'               => $this->getApiUrl('extsign.api'),
                'app_id'            => config_item('fadada_api_app_id'),
                'timestamp'         => $msgDigest['md5']['timestamp'],
                'transaction_id'    => $transactionId,
                'contract_id'       => $contractId,
                'customer_id'       => $customerId,
                'doc_title'         => urlencode($docTitle),
                'sign_keyword'      => config_item('fadada_customer_sign_key_word'),
                'return_url'        => $returnUrl,
                'notify_url'        => $notifyUrl,
                'msg_digest'        => $this->getMsgDigest($msgDigest),
            );
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }

        return $data;
    }

    /**
     * 文档签署接口(自动签)
     * 自动签主要用于接入平台的签章
     */
    public function extsignAuto($transactionId, $contractId, $docTitle, $customerId, $signKeyword, $notifyUrl = '')
    {
        try {
            $url        = $this->getApiUrl('extsign_auto.api');

            $reqData    = array(
                'transaction_id' => $transactionId,
                'contract_id'    => $contractId,
                'customer_id'    => $customerId,
                'client_role'    => 1,
                'doc_title'      => $docTitle,
                'sign_keyword'   => $signKeyword,
            );

            if (!empty($notifyUrl)) {
                $reqData['notify_url'] = $notifyUrl;
            }
            $msgDigest  = array(
                'sha1' => [config_item('fadada_api_app_secret'), $customerId],
                'md5'  => [
                    'transaction_id' => $transactionId,
                    'timestamp'      => date('YmdHis'),
                ],
            );
            $res = $this->requestFdd($url, $reqData, $msgDigest);
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
        return $res;
    }

    /**
     * 向法大大系统发送请求
     */
    private function requestFdd($url, $option, array $msgDigestArr, $method = 'POST')
    {
        $option['app_id']       = config_item('fadada_api_app_id');
        $option['timestamp']    = $msgDigestArr['md5']['timestamp'];
        $option['msg_digest']   = $this->getMsgDigest($msgDigestArr);

        $request = (new Client())->request($method, $url, ['form_params' => $option])->getBody()->getContents();
        $request = json_decode($request, true);
        log_message('error',json_encode($request));
        if ($request['result'] != 'success') {
            throw new Exception($request['msg']);
        }
        return $request;
    }

    /**
     * 生成各个接口的具体URL
     */
    private function getApiUrl($target)
    {
        return  config_item('fadada_api_base_url'). $target;
    }

    /**
     * 生成消息摘要
     * 输入参数中的两个数组要注意参数的顺序
     */
    public function getMsgDigest(array $msgDigestArr)
    {
        $md5Str     = '';
        $sha1Str    = '';
        foreach ($msgDigestArr['md5'] as $str) {
            $md5Str .= $str;
        }
        foreach ($msgDigestArr['sha1'] as $str) {
            $sha1Str .= $str;
        }
        $md5Str     = strtoupper(md5($md5Str));
        $sha1Str    = strtoupper(sha1($sha1Str));
        $orgStr = config_item('fadada_api_app_id') . $md5Str . $sha1Str;
        if (isset($msgDigestArr['other'])) {
            $orgStr .= $msgDigestArr['other'];
        }
        return base64_encode(strtoupper(sha1($orgStr)));
    }
}
