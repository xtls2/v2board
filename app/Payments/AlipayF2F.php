<?php

/**
 * 自己写别抄，抄NMB抄
 */

namespace App\Payments;

use Illuminate\Support\Facades\Log;
use Omnipay\Alipay\Responses\AopCompletePurchaseResponse;
use Omnipay\Alipay\Responses\AopTradePreCreateResponse;
use Omnipay\Omnipay;

class AlipayF2F
{
    public $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function form()
    {
        return [
            'app_id' => [
                'label' => '支付宝APPID',
                'description' => '',
                'type' => 'input',
            ],
            'private_key' => [
                'label' => '商家私钥',
                'description' => '',
                'type' => 'input',
            ],
            'public_key' => [
                'label' => '商家公钥',
                'description' => '',
                'type' => 'input',
            ],
            'alipay_public_key' => [
                'label' => '支付宝公钥',
                'description' => '',
                'type' => 'input'
            ],
        ];
    }

    /**
     * @throws \Exception
     */
    public function pay($order): array
    {

        //create gate way
        $gateway = Omnipay::create('Alipay_AopF2F');
        $gateway->setSignType('RSA2'); //RSA/RSA2
        $gateway->setAppId($this->config['app_id']);
        $gateway->setPrivateKey($this->config['private_key']); // 可以是路径，也可以是密钥内容
        $gateway->setAlipayPublicKey($this->config['public_key']); // 可以是路径，也可以是密钥内容
        $gateway->setNotifyUrl($order['notify_url']);


        $request = $gateway->purchase();
        $request->setBizContent([
            'subject' => config('v2board.app_name', 'V2Board') . '-subscribe',
            'out_trade_no' => $order['trade_no'],
            'total_amount' => $order['total_amount'] / 100
        ]);
        /** @var AopTradePreCreateResponse $response */
        $response = $request->send();
        $result = $response->getAlipayResponse();
        if ($result['code'] !== '10000') {
            throw new \Exception($result["sub_msg"]);
        }
        return [
            'type' => 0, // 0:qrcode 1:url
            'data' => $response->getQrCode()
        ];
    }

    /**
     * notify
     *
     * @param $params
     * @return array|false
     */
    public function notify($params)
    {
        $gateway = Omnipay::create('Alipay_AopF2F');
        $gateway->setSignType('RSA2'); //RSA/RSA2
        $gateway->setAppId($this->config['app_id']);
        $gateway->setPrivateKey($this->config['private_key']); // 可以是路径，也可以是密钥内容
        $gateway->setAlipayPublicKey($this->config['alipay_public_key']); // 可以是路径，也可以是密钥内容

        $request = $gateway->completePurchase();

        $request->setParams($_POST); //Optional

        /**
         * @var AopCompletePurchaseResponse $response
         */
        $response = $request->send();
        return $response->isPaid() ? [
            'trade_no' => $params['out_trade_no'],
            'callback_no' => $params['trade_no']
        ] : false;
    }
}
