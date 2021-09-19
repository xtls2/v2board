<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\CouponException;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use App\Models\Coupon;
use Illuminate\Http\Response;

class CouponController extends Controller
{

    /**
     * check
     *
     * @param Request $request
     * @return ResponseFactory|Response
     */
    public function check(Request $request)
    {
        $sessionID = $request->session()->get('id');
        $reqCode = $request->input("code");
        $reqPlanID = $request->input("plan_id", 0);
        if (empty($reqCode)) {
            abort(500, __('Coupon cannot be empty'));
        }

        /**
         * @var Coupon $coupon
         */
        try {
            $coupon = Coupon::checkCode($reqCode, $reqPlanID, $sessionID);
        } catch (CouponException $e) {
            abort($e->getCode(), $e->getMessage());
        }

        return response([
            'data' => $coupon
        ]);
    }
}
