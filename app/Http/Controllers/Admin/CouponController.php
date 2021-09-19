<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CouponGenerate;
use App\Models\Coupon;
use App\Utils\Helper;
use Exception;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class CouponController extends Controller
{
    /**
     * fetch
     *
     * @param Request $request
     * @return ResponseFactory|Response
     */
    public function fetch(Request $request)
    {
        $reqCurrent = $request->input('current') ?: 1;
        $reqPageSize = $request->input('pageSize') >= 10 ? $request->input('pageSize') : 10;
        $reqSortType = in_array($request->input('sort_type'), ["ASC", "DESC"]) ? $request->input('sort_type') : "DESC";
        $reqSort = $request->input('sort') ? $request->input('sort') : Coupon::FIELD_ID;
        $builder = Coupon::orderBy($reqSort, $reqSortType);
        $total = $builder->count();
        $coupons = $builder->forPage($reqCurrent, $reqPageSize)->get();


        return response([
            'data' => $coupons,
            'total' => $total
        ]);
    }

    /**
     * generate
     *
     * @param CouponGenerate $request
     * @return ResponseFactory|Response|void
     */
    public function generate(CouponGenerate $request)
    {
        if ($request->input('generate_count')) {
            $this->multiGenerate($request);
            return;
        }

        $reqParams = $request->validated();
        $reqLimitPlanIds = $reqParams[Coupon::FIELD_LIMIT_PLAN_IDS] ?? null;
        $reqId = $reqParams[Coupon::FIELD_ID] ?? 0;
        $reqCode = $reqParams[Coupon::FIELD_CODE] ?? null;
        $reqName = $reqParams[Coupon::FIELD_NAME];
        $reqValue = $reqParams[Coupon::FIELD_VALUE];
        $reqStartedAt = $reqParams[Coupon::FIELD_STARTED_AT];
        $reqEndAt = $reqParams[Coupon::FIELD_ENDED_AT];
        $reqLimitUse = $reqParams[Coupon::FIELD_LIMIT_USE] ?? null;
        $reqLimitUseWithUser = $reqParams[Coupon::FIELD_LIMIT_USE_WITH_USER] ?? null;
        $reqType = $reqParams[Coupon::FIELD_TYPE];

        if ($reqId <= 0) {
            $coupon = new Coupon();
            if (empty($reqCode)) {
                $reqCode = Helper::randomChar(8);
            }
        } else {
            $coupon = Coupon::Find($reqId);
            if (!$coupon) {
                abort(500, '优惠券不存在');
            }
        }
        if ($reqCode) {
            $coupon->setAttribute(Coupon::FIELD_CODE, $reqCode);
        }

        $coupon->setAttribute(Coupon::FIELD_LIMIT_PLAN_IDS, $reqLimitPlanIds);
        $coupon->setAttribute(Coupon::FIELD_TYPE, $reqType);
        $coupon->setAttribute(Coupon::FIELD_NAME, $reqName);
        $coupon->setAttribute(Coupon::FIELD_VALUE, $reqValue);
        $coupon->setAttribute(Coupon::FIELD_STARTED_AT, $reqStartedAt);
        $coupon->setAttribute(Coupon::FIELD_ENDED_AT, $reqEndAt);
        $coupon->setAttribute(Coupon::FIELD_LIMIT_USE, $reqLimitUse);
        $coupon->setAttribute(Coupon::FIELD_LIMIT_USE_WITH_USER, $reqLimitUseWithUser);


        if (!$coupon->save()) {
            abort(500, '保存失败');
        }

        return response([
            'data' => true
        ]);
    }


    /**
     * multi generate
     *
     * @param CouponGenerate $request
     *
     * @return void
     */
    private function multiGenerate(CouponGenerate $request)
    {
        $reqParams = $request->validated();

        $reqGenerateCount = (int)$reqParams['generate_count'];
        $reqName = $reqParams[Coupon::FIELD_NAME];
        $reqValue = $reqParams[Coupon::FIELD_VALUE];
        $reqStartedAt = $reqParams[Coupon::FIELD_STARTED_AT];
        $reqEndAt = $reqParams[Coupon::FIELD_ENDED_AT];
        $reqLimitUse = $reqParams[Coupon::FIELD_LIMIT_USE] ?? null;
        $reqType = $reqParams[Coupon::FIELD_TYPE];
        $reqLimitPlanIds = $reqParams[Coupon::FIELD_LIMIT_PLAN_IDS] ?? null;
        $reqLimitUseWithUser = $reqParams[Coupon::FIELD_LIMIT_USE_WITH_USER] ?? null;

        DB::beginTransaction();

        $coupons = [];
        for ($i = 0; $i < $reqGenerateCount; $i++) {
            $coupon = new Coupon();
            $coupon->setAttribute(Coupon::FIELD_CODE, Helper::randomChar(8));
            $coupon->setAttribute(Coupon::FIELD_NAME, $reqName);
            $coupon->setAttribute(Coupon::FIELD_VALUE, $reqValue);
            $coupon->setAttribute(Coupon::FIELD_STARTED_AT, $reqStartedAt);
            $coupon->setAttribute(Coupon::FIELD_ENDED_AT, $reqEndAt);
            $coupon->setAttribute(Coupon::FIELD_LIMIT_USE, $reqLimitUse);
            $coupon->setAttribute(Coupon::FIELD_TYPE, $reqType);
            $coupon->setAttribute(Coupon::FIELD_LIMIT_PLAN_IDS, $reqLimitPlanIds);
            $coupon->setAttribute(Coupon::FIELD_LIMIT_USE_WITH_USER, $reqLimitUseWithUser);

            if (!$coupon->save()) {
                DB::rollBack();
                abort(500, '生成失败');
            }
            array_push($coupons, $coupon);
        }
        DB::commit();

        $data = "名称,类型,金额或比例,开始时间,结束时间,可用次数,可用于订阅,券码,生成时间\r\n";
        foreach ($coupons as $coupon) {
            $name = $coupon->getAttribute(Coupon::FIELD_NAME);
            $code = $coupon->getAttribute(Coupon::FIELD_CODE);
            $type = ['', '金额', '比例'][$coupon->getAttribute(Coupon::FIELD_TYPE)];
            $value = ['', ($coupon->getAttribute(Coupon::FIELD_VALUE) / 100),
                $coupon->getAttribute(Coupon::FIELD_VALUE)][$coupon->getAttribute(Coupon::FIELD_TYPE)];
            $startTime = date('Y-m-d H:i:s', $coupon->getAttribute(Coupon::FIELD_STARTED_AT));
            $endTime = date('Y-m-d H:i:s', $coupon->getAttribute(Coupon::FIELD_ENDED_AT));
            $limitUse = $coupon->getAttribute(Coupon::FIELD_LIMIT_USE) ?? '不限制';
            $createTime = date('Y-m-d H:i:s', time());
            $limitPlanIds = is_array($coupon->getAttribute(Coupon::FIELD_LIMIT_PLAN_IDS))
                ? implode("/", $coupon->getAttribute(Coupon::FIELD_LIMIT_PLAN_IDS)) : '不限制';
           $data .= "{$name},{$type},{$value},{$startTime},{$endTime},{$limitUse},{$limitPlanIds},{$code},{$createTime}\r\n";
        }
        echo $data;
    }

    /**
     * drop
     *
     * @param Request $request
     * @return ResponseFactory|Response
     * @throws Exception
     */
    public function drop(Request $request)
    {
        $reqId = (int)$request->input('id');
        if ($reqId <= 0) {
            abort(500, '参数有误');
        }

        /**
         * @var Coupon $coupon
         */
        $coupon = Coupon::find($reqId);
        if ($coupon == null) {
            abort(500, '优惠券不存在');
        }

        if (!$coupon->delete()) {
            abort(500, '删除失败');
        }

        return response([
            'data' => true
        ]);
    }
}
