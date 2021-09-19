<?php

namespace App\Services;


use App\Models\Order;
use App\Models\Payment;

class PaymentService
{
    /**
     * @var string
     */
    public $customResult;
    /**
     * @var array
     */
    private $_config;
    /**
     * @var string
     */
    private $_method;
    /**
     * @var mixed
     */
    private $_paymentInstance;


    /**
     * PaymentService constructor.
     * @param $method
     * @param Payment|null $payment
     * @throws \Exception
     */
    public function __construct($method, Payment $payment = null)
    {
        $this->_method = $method;
        $className = '\\App\\Payments\\' . $this->_method;
        if (!class_exists($className)) {
            throw new \Exception("gate is not found");
        }

        $this->_config = [];
        if (isset($payment)) {
            $this->_config = $payment->getAttribute(Payment::FIELD_CONFIG);
            $this->_config['enable'] = $payment->getAttribute(Payment::FIELD_ENABLE);
            $this->_config['id'] = $payment->getAttribute(Payment::FIELD_ID);
            $this->_config['uuid'] = $payment->getAttribute(Payment::FIELD_UUID);
        };
        $this->_paymentInstance = new $className($this->_config);

        if (isset($this->payment->customResult)) {
            $this->customResult = $this->payment->customResult;
        }

    }

    /**
     * notify
     *
     * @param $params
     * @return mixed
     * @throws \Exception
     */
    public function notify($params)
    {
        if (!$this->_config['enable']) {
            throw new \Exception('gate is not enable');
        }
        return $this->_paymentInstance->notify($params);
    }

    /**
     * pay
     *
     * @param Order $order
     * @param string $stripeToken
     *
     * @return mixed
     */
    public function pay(Order $order, string $stripeToken ="")
    {
        return $this->_paymentInstance->pay([
            'notify_url' => url("/api/v1/guest/payment/notify/{$this->_method}/{$this->_config['uuid']}"),
            'return_url' => config('v2board.app_url', env('APP_URL')) . '/#/order/' . $order->getAttribute(Order::FIELD_TRADE_NO),
            'trade_no' => $order->getAttribute(Order::FIELD_TRADE_NO),
            'total_amount' => $order->getAttribute(Order::FIELD_TOTAL_AMOUNT),
            'user_id' => $order->getAttribute(Order::FIELD_USER_ID),
            'stripe_token' => $stripeToken
        ]);
    }

    public function form()
    {
        $form = $this->_paymentInstance->form();
        $keys = array_keys($form);
        foreach ($keys as $key) {
            if (isset($this->_config[$key])) {
                $form[$key]['value'] = $this->_config[$key];
            }
        }
        return $form;
    }
}
