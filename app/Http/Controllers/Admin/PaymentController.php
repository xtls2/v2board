<?php

namespace App\Http\Controllers\Admin;

use App\Services\PaymentService;
use App\Utils\Helper;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Response;

class PaymentController extends Controller
{
    /**
     * methods
     *
     * @return Application|ResponseFactory|Response
     */
    public function methods()
    {
        $methods = [];
        foreach (glob(base_path('app//Payments') . '/*.php') as $file) {
            array_push($methods, pathinfo($file)['filename']);
        }
        return response([
            'data' => $methods
        ]);
    }

    /**
     * fetch
     *
     * @return Application|ResponseFactory|Response
     */
    public function fetch()
    {
        $payments = Payment::all();
        foreach ($payments as $payment) {
            $payment['notify_url'] = url("/api/v1/guest/payment/notify/{$payment->getAttribute(Payment::FIELD_PAYMENT)}/{$payment->getAttribute(Payment::FIELD_UUID)}");
        }
        return response([
            'data' => $payments
        ]);
    }

    /**
     * form
     *
     * @param Request $request
     * @return Application|ResponseFactory|Response
     * @throws Exception
     */
    public function form(Request $request)
    {
        $reqPayment = $request->input('payment');
        $paymentService = new PaymentService($reqPayment);
        return response([
            'data' => $paymentService->form()
        ]);
    }

    /**
     * save
     *
     * @param Request $request
     * @return Application|ResponseFactory|Response
     */
    public function save(Request $request)
    {
        $reqId = (int)$request->input('id');
        $reqName = $request->input('name');
        $reqPayment = $request->input('payment');
        $reqConfig = $request->input('config');
        $reqEnable = $request->input('enable');

        /**
         * @var Payment $payment
         */
        if ($reqId > 0) {
            $payment = Payment::find($reqId);
            if (!$payment === null) {
                abort(500, '支付方式不存在');
            }
        } else {
            $payment = new Payment();
            $payment->setAttribute(Payment::FIELD_UUID, Helper::randomChar(8));
        }

        if ($reqName !== null) {
            $payment->setAttribute(Payment::FIELD_NAME, $reqName);
        }

        if ($reqPayment !== null) {
            $payment->setAttribute(Payment::FIELD_PAYMENT, $reqPayment);
        }

        if ($reqPayment !== null) {
            $payment->setAttribute(Payment::FIELD_CONFIG, $reqConfig);
        }

        if ($reqEnable !== null) {
            $payment->setAttribute(Payment::FIELD_ENABLE, (int)$reqEnable);
        }

        if (!$payment->save()) {
            abort(500, '保存失败');
        }

        return response([
            'data' => true
        ]);
    }

    /**
     * 删除失败
     *
     * @param Request $request
     * @return Application|ResponseFactory|Response
     */
    public function drop(Request $request)
    {
        $reqId = $request->input('id');
        $payment = Payment::find($reqId);
        if ($payment === null) {
            abort(500, '支付方式不存在');
        }

        try {
            $payment->delete();
        } catch (Exception $e) {
            abort(500, "删除失败");
        }

        return response([
            'data' => true
        ]);
    }
}
