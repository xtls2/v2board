<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\PlanSave;
use App\Http\Requests\Admin\PlanSort;
use App\Http\Requests\Admin\PlanUpdate;
use Exception;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class PlanController extends Controller
{
    /**
     * fetch
     *
     * @param Request $request
     * @return ResponseFactory|Response
     */
    public function fetch(Request $request)
    {
        $plans = Plan::orderBy(Plan::FIELD_SORT, "ASC")->get();
        foreach ($plans as $plan) {
            /**
             * @var Plan $plan
             */
            $planId = $plan->getKey();
            $plan->setAttribute("count", User::countEffectivePlanUsers($planId));
        }
        return response([
            'data' => $plans
        ]);
    }

    public function save(PlanSave $request)
    {
        $reqId = (int)$request->input('id');

        $reqName = $request->input('name');
        $reqContent = $request->input('content');
        $reqGroupId = (int)$request->input("group_id");
        $reqTransferEnable = $request->input('transfer_enable');
        $reqMonthPrice = $request->input("month_price");
        $reqQuarterPrice = $request->input("quarter_price");
        $reqHalfYearPrice = $request->input("half_year_price");
        $reqYearPrice = $request->input("year_price");
        $reqTwoYearPrice = $request->input("two_year_price");
        $reqThreeYearPrice = $request->input("three_year_price");
        $reqOneTimePrice = $request->input("onetime_price");
        $reqResetPrice = $request->input("reset_price");

        DB::beginTransaction();

        if ($reqId > 0) {

            /**
             * @var Plan $plan
             */
            $plan = Plan::find($reqId);
            if ($plan == null) {
                abort(500, '该订阅不存在');
            }
            // update user group id and transfer
            // xxx 设计有问题
            $planUsers = $plan->users();
            foreach ($planUsers as $user) {
                /**
                 * @var User $user
                 */
                $user->setAttribute(User::FIELD_GROUP_ID, $reqGroupId);
                $user->setAttribute(User::FIELD_TRANSFER_ENABLE, $reqTransferEnable * 1073741824);
                if (!$user->save()) {
                    DB::rollBack();
                    abort(500, '用户更新失败');
                }
            }
        } else {
            $plan = new Plan();
        }

        $plan->setAttribute(Plan::FIELD_NAME, $reqName);
        $plan->setAttribute(Plan::FIELD_GROUP_ID, $reqGroupId);
        $plan->setAttribute(Plan::FIELD_TRANSFER_ENABLE, $reqTransferEnable);
        if ($reqContent) {
            $plan->setAttribute(Plan::FIELD_CONTENT, $reqContent);
        }

        if ($reqMonthPrice !== null) {
            $plan->setAttribute(Plan::FIELD_MONTH_PRICE, $reqMonthPrice);
        }

        if ($reqQuarterPrice !== null) {
            $plan->setAttribute(Plan::FIELD_QUARTER_PRICE, $reqQuarterPrice);
        }


        if ($reqHalfYearPrice !== null) {
            $plan->setAttribute(Plan::FIELD_HALF_YEAR_PRICE, $reqHalfYearPrice);
        }

        if ($reqYearPrice !== null) {
            $plan->setAttribute(Plan::FIELD_YEAR_PRICE, $reqYearPrice);
        }

        if ($reqTwoYearPrice !== null) {
            $plan->setAttribute(Plan::FIELD_TWO_YEAR_PRICE, $reqTwoYearPrice);
        }

        if (!$reqThreeYearPrice !== null) {
            $plan->setAttribute(Plan::FIELD_THREE_YEAR_PRICE, $reqThreeYearPrice);
        }

        if ($reqOneTimePrice !== null) {
            $plan->setAttribute(Plan::FIELD_ONETIME_PRICE, $reqOneTimePrice);
        }

        if ($reqResetPrice !== null) {
            $plan->setAttribute(Plan::FIELD_RESET_PRICE, $reqResetPrice);
        }

        if (!$plan->save()) {
            DB::rollBack();
            abort(500, '保存失败');
        }
        DB::commit();

        return response([
            'data' => true
        ]);
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
            abort(500, "参数错误");
        }
        /**
         * @var Plan $plan
         */
        $plan = Plan::find($reqId);
        if ($plan == null) {
            abort(500, '该订阅ID不存在');
        }

        if (Order::where(Order::FIELD_PLAN_ID, $reqId)->count() > 0) {
            abort(500, '该订阅下存在订单无法删除');
        }
        if (User::where(User::FIELD_PLAN_ID, $reqId)->count() > 0) {
            abort(500, '该订阅下存在用户无法删除');
        }

        try {
            $plan->delete();
        } catch (Exception $e) {
            abort(500, '删除失败-' . $e->getMessage());
        }

        return response([
            'data' => true
        ]);
    }

    /**
     * update
     *
     * @param PlanUpdate $request
     * @return ResponseFactory|Response
     */
    public function update(PlanUpdate $request)
    {
        $reqId = (int)$request->input('id');
        $reqShow = $request->input("show");
        $reqRenew = $request->input('renew');

        /**
         * @var Plan $plan
         */
        $plan = Plan::find($reqId);
        if ($plan == null) {
            abort(500, '该订阅不存在');
        }

        if ($reqRenew !== null) {
            $plan->setAttribute(Plan::FIELD_RENEW, (int)$reqRenew);
        }

        if ($reqShow !== null) {
            $plan->setAttribute(Plan::FIELD_SHOW, (int)$reqShow);
        }

        if (!$plan->save()) {
            abort(500, '保存失败');
        }

        return response([
            'data' => true
        ]);
    }

    /**
     * sort
     *
     * @param PlanSort $request
     * @return ResponseFactory|Response
     */
    public function sort(PlanSort $request)
    {
        $reqIds = (array)$request->input('plan_ids');
        DB::beginTransaction();
        foreach ($reqIds as $k => $id) {
            /**
             * @var Plan $plan
             */
            $plan = Plan::find($id);
            if ($plan == null) {
                DB::rollBack();
                abort(500, '知识数据异常');
            }

            $plan->setAttribute(Plan::FIELD_SORT, $k + 1);
            if (!$plan->save()) {
                DB::rollBack();
                abort(500, '保存失败');
            }
        }
        DB::commit();
        return response([
            'data' => true
        ]);
    }
}
