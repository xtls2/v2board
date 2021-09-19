<?php

namespace App\Http\Controllers\Guest;

use App\Jobs\SendTelegramJob;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentService;
use Exception;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * @throws Exception
     */
    public function notify($method, $uuid, Request $request)
    {
        try {

            $payment = Payment::findByUUID($uuid);
            if ($payment === null) {
                throw new Exception("payment not found");
            }

            $paymentService = new PaymentService($method, $payment);
            $verify = $paymentService->notify($request->input());
            if (!$verify) {
                throw new Exception("verify error");
            }

            $tradeNo = $verify['trade_no'];
            $callbackNo = $verify['callback_no'];
            /**
             * @var Order $order
             */
            $order = Order::findByTradeNo($tradeNo);
            if ($order === null) {
                throw new Exception("order not found");
            }

            if ($order->getAttribute(Order::FIELD_STATUS) !== Order::STATUS_UNPAID) {
                Log::error("invalid order status", ['order' => $order->toArray(), "verify" => $verify]);
                throw new Exception("invalid order status");
            }

            $order->setAttribute(Order::FIELD_PAID_AT, time());
            $order->setAttribute(Order::FIELD_STATUS, Order::STATUS_PENDING);
            $order->setAttribute(Order::FIELD_CALLBACK_NO, $callbackNo);

            if (!$order->save()) {
                throw new Exception("order save failed");
            }

            $this->_notifyAdmin($order);

        } catch (Exception $e) {
            Log::error($e);
            abort(500, 'fail: ' . $e->getMessage());
        }

        die($paymentService->customResult ?? 'success');
    }

    /**
     * 通知管理员
     *
     * @param Order $order
     * @return void
     */
    private function _notifyAdmin(Order $order): void
    {
        //通知
        $message = sprintf(
            "💰成功收款%s元\n———————————————\n订单号：%s",
            $order->getAttribute(Order::FIELD_TOTAL_AMOUNT) / 100,
            $order->getAttribute(Order::FIELD_TRADE_NO)
        );

        $adminUsers = User::findAdminUsers();
        if (count($adminUsers) == 0) {
            return;
        }
        /**
         * @var  User $user
         */
        foreach ($adminUsers as $user) {
            $telegramId = $user->getAttribute(User::FIELD_TELEGRAM_ID);
            if ($telegramId > 0) {
                SendTelegramJob::dispatch($telegramId, $message);
            }
        }
    }
}
