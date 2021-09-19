<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\UserFetch;
use App\Http\Requests\Admin\UserGenerate;
use App\Http\Requests\Admin\UserSendMail;
use App\Http\Requests\Admin\UserUpdate;
use App\Jobs\SendEmailJob;
use App\Utils\Helper;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Plan;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * reset secret
     *
     * @param Request $request
     * @return Application|ResponseFactory|Response
     */
    public function resetSecret(Request $request)
    {
        $reqId = $request->input("id");
        $user = User::find($reqId);
        /**
         * @var User $user
         */
        if ($user == null) {
            abort(500, '用户不存在');
        }

        $user->setAttribute(User::FIELD_TOKEN, Helper::guid());
        $user->setAttribute(User::FIELD_UUID, Helper::guid(true));
        return response([
            'data' => $user->save()
        ]);
    }

    /**
     * _filter
     *
     * @param Request $request
     * @param $builder
     */
    private function _filter(Request $request, $builder)
    {
        $reqFilter = (array)$request->input('filter');
        foreach ($reqFilter as $filter) {
            if ($filter['key'] === 'invite_by_email') {
                /**
                 * @var User $user
                 */
                $user = User::findByEmail($filter['value']);
                if ($user === null) {
                    continue;
                }
                $builder->where(User::FIELD_INVITE_USER_ID, $user->getKey());
                continue;
            }
            if ($filter['key'] === User::FIELD_D || $filter['key'] === User::FIELD_TRANSFER_ENABLE) {
                $filter['value'] = $filter['value'] * 1073741824;
            }
            //兼容
            if ($filter['condition'] === '模糊' || $filter['condition'] === 'like') {
                $filter['condition'] = 'like';
                $filter['value'] = "%{$filter['value']}%";
            }
            $builder->where($filter['key'], $filter['condition'], $filter['value']);
        }

    }

    /**
     * fetch
     *
     * @param UserFetch $request
     * @return Application|ResponseFactory|Response
     */
    public function fetch(UserFetch $request)
    {
        $reqCurrent = $request->input('current') ? $request->input('current') : 1;
        $reqPageSize = $request->input('pageSize') >= 10 ? $request->input('pageSize') : 10;
        $reqSortType = in_array($request->input('sort_type'), ["ASC", "DESC"]) ? $request->input('sort_type') : "DESC";
        $reqSort = $request->input('sort') ? $request->input('sort') : User::FIELD_ID;
        $userModel = User::orderBy($reqSort, $reqSortType);
        $this->_filter($request, $userModel);
        $total = $userModel->count();
        $users = $userModel->forPage($reqCurrent, $reqPageSize)->get();
        $plans = Plan::get();

        foreach ($users as $user) {
            /**
             * @var User $user
             */
            foreach ($plans as $plan) {
                /**
                 * @var Plan $plan
                 */
                $planId = $plan->getKey();
                $userPlanId = $user->getAttribute(User::FIELD_PLAN_ID);
                if ($planId == $userPlanId) {
                    $user->setAttribute("plan_name", $plan->getAttribute(Plan::FIELD_NAME));
                }
            }

            $subscribeUrl = Helper::getSubscribeHost()  . '/api/v1/client/subscribe?token=' . $user->getAttribute(User::FIELD_TOKEN);
            $user->setAttribute("subscribe_url", $subscribeUrl);
        }
        return response([
            'data' => $users,
            'total' => $total
        ]);
    }


    /**
     * userInfo
     *
     * @param Request $request
     * @return Application|ResponseFactory|Response
     */
    public function userInfo(Request $request)
    {
        $reqId = (int)$request->input('id');
        if ($reqId <= 0) {
            abort(500, '参数错误');
        }

        /**
         * @var User $user
         */
        $user = User::find($reqId);
        if ($user === null) {
            abort(500, '用户不存在');
        }
        $inviteUserId = $user->getAttribute(User::FIELD_INVITE_USER_ID);
        if ($inviteUserId > 0) {
            $user["invite_user"] = User::find($inviteUserId);
        }

        return response([
            'data' => $user
        ]);
    }

    /**
     * update
     *
     * @param UserUpdate $request
     * @return Application|ResponseFactory|Response
     */
    public function update(UserUpdate $request)
    {
        $reqId = $request->input("id");
        $reqEmail = $request->input("email");
        $reqPassword = (string)$request->input("password");
        $reqPlanId = (int)$request->input("plan_id");
        $reqTransferEnable = $request->input("transfer_enable");
        $reqExpiredAt = $request->input("expired_at");
        $reqBanned = $request->input("banned");
        $reqCommissionRate = $request->input("commission_rate");
        $reqDiscount = $request->input("discount");
        $reqIsAdmin = $request->input("is_admin");
        $reqIsStaff = $request->input("is_staff");
        $reqU = $request->input('u');
        $reqD = $request->input('d');
        $reqBalance = $request->input('balance');
        $reqCommissionType = $request->input('commission_type');
        $reqCommissionBalance = $request->input('commission_balance');
        $reqRemarks = $request->input("remarks");
        $reqInviteUserEmail = $request->input('invite_user_email');
        $reqInviteUserID = $request->input('invite_user_id');


        /**
         * @var User $user
         */
        $user = User::find($reqId);
        if (!$user) {
            abort(500, '用户不存在');
        }

        $userEmail = $user->getAttribute(User::FIELD_EMAIL);
        if (User::findByEmail($reqEmail) && $userEmail !== $reqEmail) {
            abort(500, '邮箱已被使用');
        }

        if ($reqPassword) {
            $user->setAttribute(User::FIELD_PASSWORD, password_hash($reqPassword, PASSWORD_DEFAULT));
            $user->setAttribute(User::FIELD_PASSWORD_ALGO, null);
        }

        if ($reqPlanId > 0) {
            /**
             * @var Plan $plan
             */
            $plan = Plan::find($reqPlanId);
            if ($plan === null) {
                abort(500, '订阅计划不存在');
            }
            $user->setAttribute(User::FIELD_PLAN_ID, $reqPlanId);
            $user->setAttribute(User::FIELD_GROUP_ID, $plan->getAttribute(PLan::FIELD_GROUP_ID));
        } else {
            $user->setAttribute(User::FIELD_PLAN_ID, 0);
            $user->setAttribute(User::FIELD_GROUP_ID, 0);
        }

        if ($reqTransferEnable != null) {
            $user->setAttribute(User::FIELD_TRANSFER_ENABLE, $reqTransferEnable);
        }

        if ($reqExpiredAt != null) {
            $user->setAttribute(User::FIELD_EXPIRED_AT, $reqExpiredAt);
        }

        if ($reqBanned != null) {
            $user->setAttribute(User::FIELD_BANNED, $reqBanned);
        }

        if ($reqCommissionRate != null) {
            $user->setAttribute(User::FIELD_COMMISSION_RATE, $reqCommissionRate);
        }

        if ($reqCommissionRate != null) {
            $user->setAttribute(User::FIELD_COMMISSION_TYPE, $reqCommissionType);
        }

        if ($reqDiscount != null) {
            $user->setAttribute(User::FIELD_DISCOUNT, $reqDiscount);
        }

        if ($reqIsAdmin != null) {
            $user->setAttribute(User::FIELD_IS_ADMIN, $reqIsAdmin);
        }

        if ($reqIsStaff != null) {
            $user->setAttribute(User::FIELD_IS_STAFF, $reqIsStaff);
        }

        if ($reqU != null) {
            $user->setAttribute(User::FIELD_U, $reqU);
        }

        if ($reqD != null) {
            $user->setAttribute(User::FIELD_D, $reqD);
        }

        if ($reqBalance != null) {
            $user->setAttribute(User::FIELD_BALANCE, $reqBalance);
        }

        if ($reqCommissionBalance != null) {
            $user->setAttribute(User::FIELD_COMMISSION_BALANCE, $reqCommissionBalance);
        }

        if ($reqRemarks != null) {
            $user->setAttribute(User::FIELD_REMARKS, $reqRemarks);
        }

        // 保留Email但不使用
        if ($reqInviteUserEmail) {
            /**
             * @var User $inviteUser
             */
            $inviteUser = User::findByEmail($reqInviteUserEmail);
            if ($inviteUser !== null) {
                $user->setAttribute(User::FIELD_INVITE_USER_ID, $inviteUser->getKey());
            } else {
                $user->setAttribute(User::FIELD_INVITE_USER_ID, 0);
            }
        }

        if ($reqInviteUserID) {
            /**
             * @var User $inviteUser
             */
            $inviteUser = User::find($reqInviteUserID);
            if ($inviteUser !== null) {
                $user->setAttribute(User::FIELD_INVITE_USER_ID, $inviteUser->getKey());
            } else {
                $user->setAttribute(User::FIELD_INVITE_USER_ID, 0);
            }
        }

        if (!$user->save()) {
            abort(500, '保存失败');

        }
        return response([
            'data' => true
        ]);
    }

    /**
     * dumpCSV
     *
     * @param Request $request
     *
     * @return void
     */
    public function dumpCSV(Request $request)
    {
        $userModel = User::orderBy(User::FIELD_ID, 'asc');
        $this->_filter($request, $userModel);
        $users = $userModel->get();
        $plans = Plan::get();


        foreach ($users as $user) {
            /**
             * @var User $user
             */
            foreach ($plans as $plan) {
                /**
                 * @var Plan $plan
                 */
                if ($plan->getKey() == $user->getAttribute(User::FIELD_PLAN_ID)) {
                    $user->setAttribute('plan_name', $plan->getAttribute(Plan::FIELD_NAME));
                }
            }
        }

        $data = "邮箱,余额,推广佣金,总流量,剩余流量,套餐到期时间,订阅计划,订阅地址\r\n";
        $baseUrl = config('v2board.subscribe_url', config('v2board.app_url', env('APP_URL')));
        foreach ($users as $user) {
            $expireDate = $user->getAttribute(User::FIELD_EXPIRED_AT) === NULL ? '长期有效' :
                date('Y-m-d H:i:s', $user->getAttribute(User::FIELD_EXPIRED_AT));
            $balance = $user->getAttribute(User::FIELD_BALANCE) / 100;
            $commissionBalance = $user->getAttribute(User::FIELD_COMMISSION_BALANCE) / 100;
            $transferEnable = $user->getAttribute(User::FIELD_TRANSFER_ENABLE) ?
                $user->getAttribute(User::FIELD_TRANSFER_ENABLE) / 1073741824 : 0;
            $notUseFlow = (($user->getAttribute(User::FIELD_TRANSFER_ENABLE) -
                        ($user->getAttribute(User::FIELD_U) + $user->getAttribute(User::FIELD_D))) / 1073741824) ?? 0;
            $planName = $user['plan_name'] ?? '无订阅';
            $subscribeUrl = $baseUrl . '/api/v1/client/subscribe?token=' . $user->getAttribute(User::FIELD_TOKEN);
            $data .= "{$user->getAttribute(User::FIELD_EMAIL)},$balance,$commissionBalance,
            $transferEnable,$notUseFlow,$expireDate,$planName,$subscribeUrl\r\n";
        }
        echo "\xEF\xBB\xBF" . $data;
    }

    /**
     * generate
     *
     * @param UserGenerate $request
     * @return Application|ResponseFactory|Response
     */
    public function generate(UserGenerate $request)
    {
        $reqGenerateCount = (int)$request->input('generate_count');
        if ($reqGenerateCount > 0) {
            return $this->_multiGenerate($request, $reqGenerateCount);
        }
        $reqEmailPrefix = $request->input('email_prefix');
        $reqPlanId = $request->input('plan_id');
        $reqExpiredAt = $request->input("expired_at");
        $reqEmailSuffix = $request->input('email_suffix');
        $reqPassword = $request->input('password');

        if (empty($reqEmailPrefix)) {
            abort(500, "参数错误");
        }
        $plan = null;
        if ($reqPlanId) {
            /**
             * @var Plan $plan
             */
            $plan = Plan::find($reqPlanId);
            if ($plan == null) {
                abort(500, '订阅计划不存在');
            }
        }

        $user = new User();
        $user->setAttribute(User::FIELD_EMAIL, $reqEmailPrefix . '@' . $reqEmailSuffix);
        $user->setAttribute(User::FIELD_PLAN_ID, $plan !== null ? $plan->getKey() : 0);
        $user->setAttribute(User::FIELD_GROUP_ID, $plan !== null ? $plan->getAttribute(Plan::FIELD_GROUP_ID) : 0);
        $user->setAttribute(User::FIELD_TRANSFER_ENABLE, $plan !== null ? $plan->getAttribute(Plan::FIELD_TRANSFER_ENABLE) : 0);
        $user->setAttribute(User::FIELD_EXPIRED_AT, $reqExpiredAt ?: null);
        $user->setAttribute(User::FIELD_UUID, Helper::guid(true));
        $user->setAttribute(User::FIELD_TOKEN, Helper::guid());
        $user->setAttribute(User::FIELD_PASSWORD, password_hash($reqPassword ??
            $user->getAttribute(User::FIELD_EMAIL), PASSWORD_DEFAULT));
        if (!$user->save()) {
            abort(500, '生成失败');
        }


        return response([
            'data' => true
        ]);
    }

    /**
     * multiGenerate
     *
     * @param Request $request
     * @param $count
     */
    private function _multiGenerate(Request $request, $count)
    {
        $reqPlanId = $request->input('plan_id');
        $reqEmailSuffix = $request->input('email_suffix');
        $reqExpiredAt = $request->input('expired_at');
        $reqPassword = $request->input('password');
        if ($reqPlanId) {
            /**
             * @var Plan $plan
             */
            $plan = Plan::find($reqPlanId);
            if ($plan == null) {
                abort(500, '订阅计划不存在');
            }
        } else {
            $plan = null;
        }
        $users = [];
        DB::beginTransaction();
        for ($i = 0; $i < $count; $i++) {
            $user = new User();
            $user->setAttribute(User::FIELD_EMAIL, Helper::randomChar(6) . '@' . $reqEmailSuffix);
            $user->setAttribute(User::FIELD_PLAN_ID, $plan !== null ? $plan->getKey() : 0);
            $user->setAttribute(User::FIELD_GROUP_ID, $plan !== null ? $plan->getAttribute(Plan::FIELD_GROUP_ID) : 0);
            $user->setAttribute(User::FIELD_TRANSFER_ENABLE, $plan !== null ? $plan->getAttribute(Plan::FIELD_TRANSFER_ENABLE) * 1073741824 : 0);
            $user->setAttribute(User::FIELD_EXPIRED_AT, $reqExpiredAt ?: null);
            $user->setAttribute(User::FIELD_UUID, Helper::guid(true));
            $user->setAttribute(User::FIELD_TOKEN, Helper::guid());
            $user->setAttribute(User::FIELD_PASSWORD, password_hash($reqPassword ?? $user->getAttribute(User::FIELD_EMAIL), PASSWORD_DEFAULT));
            $user->setAttribute(User::FIELD_CREATED_AT, time());
            $user->setAttribute(User::FIELD_UPDATED_AT, time());
            if (!$user->save()) {
                DB::rollBack();
                abort(500, '生成失败');
            }
            array_push($users, $user);
        }
        DB::commit();
        $data = "账号,密码,过期时间,UUID,创建时间,订阅地址\r\n";
        $baseUrl = config('v2board.subscribe_url', config('v2board.app_url', env('APP_URL')));
        foreach ($users as $user) {
            /**
             * @var User $user
             */
            $expireDate = empty($user->getAttribute(User::FIELD_EXPIRED_AT)) ? '长期有效' :
                date('Y-m-d H:i:s', $user->getAttribute(User::FIELD_EXPIRED_AT));
            $createDate = date('Y-m-d H:i:s', $user->getAttribute(User::FIELD_CREATED_AT));
            $password = $reqPassword ?? $user->getAttribute(User::FIELD_EMAIL);
            $subscribeUrl = $baseUrl . '/api/v1/client/subscribe?token=' . $user->getAttribute(User::FIELD_TOKEN);
            $data .= "{$user['email']},$password,$expireDate,{$user['uuid']},$createDate,$subscribeUrl\r\n";
        }
        echo $data;
    }

    /**
     * send mail
     *
     * @param UserSendMail $request
     * @return Application|ResponseFactory|Response
     */
    public function sendMail(UserSendMail $request)
    {
        $reqSortType = in_array($request->input('sort_type'), ["ASC", "DESC"]) ?
            $request->input('sort_type') : "DESC";
        $reqSort = $request->input('sort') ? $request->input('sort') : User::FIELD_CREATED_AT;
        $reqSubject = $request->input('subject');
        $reqContent = $request->input('content');

        $builder = User::orderBy($reqSort, $reqSortType);
        $this->_filter($request, $builder);
        $users = $builder->get();
        foreach ($users as $user) {
            SendEmailJob::dispatch([
                'email' => $user->email,
                'subject' => $reqSubject,
                'template_name' => 'notify',
                'template_value' => [
                    'name' => config('v2board.app_name', 'V2Board'),
                    'url' => config('v2board.app_url'),
                    'content' => $reqContent
                ]
            ]);
        }
        return response([
            'data' => true
        ]);
    }

    /**
     * ban
     *
     * @param Request $request
     * @return Application|ResponseFactory|Response
     */
    public function ban(Request $request)
    {
        $reqSortType = in_array($request->input('sort_type'), ["ASC", "DESC"]) ? $request->input('sort_type') : "DESC";
        $reqSort = $request->input('sort') ? $request->input('sort') : User::FIELD_CREATED_AT;
        $builder = User::orderBy($reqSort, $reqSortType);
        $this->_filter($request, $builder);
        try {
            $builder->update([
                'banned' => 1
            ]);
        } catch (Exception $e) {
            abort(500, '处理失败');
        }

        return response([
            'data' => true
        ]);
    }
}
