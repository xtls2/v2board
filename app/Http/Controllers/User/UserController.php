<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UserChangePassword;
use App\Http\Requests\User\UserTransfer;
use App\Http\Requests\User\UserUpdate;
use App\Models\User;
use App\Utils\CacheKey;
use App\Utils\Helper;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;


class UserController extends Controller
{
    /**
     * logout
     *
     * @param Request $request
     * @return ResponseFactory|Response
     */
    public function logout(Request $request)
    {
        $request->session()->flush();
        return response([
            'data' => true
        ]);
    }

    /**
     * change password
     *
     * @param UserChangePassword $request
     * @return ResponseFactory|Response
     */
    public function changePassword(UserChangePassword $request)
    {
        $sessionId = $request->session()->get('id');
        $reqOldPassword = $request->input('old_password');
        $reqNewPassword = $request->input('new_password');
        /**
         * @var User $user
         */
        $user = User::find($sessionId);
        if ($user == null) {
            abort(500, __('The user does not exist'));
        }

        if (!Helper::multiPasswordVerify(
            $user->getAttribute(User::FIELD_PASSWORD_ALGO),
            $user->getAttribute(User::FIELD_PASSWORD_SALT),
            $reqOldPassword, $user->getAttribute(User::FIELD_PASSWORD))) {
            abort(500, __('The old password is wrong'));
        }

        $user->setAttribute(User::FIELD_PASSWORD, password_hash($reqNewPassword, PASSWORD_DEFAULT));
        $user->setAttribute(User::FIELD_PASSWORD_ALGO, NULL);
        $user->setAttribute(User::FIELD_PASSWORD_SALT, NULL);
        if (!$user->save()) {
            abort(500, __('Save failed'));
        }
        $request->session()->flush();
        return response([
            'data' => true
        ]);
    }

    /**
     * info
     *
     * @param Request $request
     * @return ResponseFactory|Response
     */
    public function info(Request $request)
    {
        $sessionId = $request->session()->get('id');
        /**
         * @var User $user
         */
        $user = User::find($sessionId);
        if ($user == NULL) {
            abort(500, __('The user does not exist'));
        }

        $data = [
            User::FIELD_EMAIL => $user->getAttribute(User::FIELD_EMAIL),
            User::FIELD_TRANSFER_ENABLE => $user->getAttribute(User::FIELD_TRANSFER_ENABLE),
            User::FIELD_LAST_LOGIN_AT => $user->getAttribute(User::FIELD_LAST_LOGIN_AT),
            User::FIELD_CREATED_AT => $user->getAttribute(User::FIELD_CREATED_AT),
            User::FIELD_BANNED => $user->getAttribute(User::FIELD_BANNED),
            User::FIELD_REMIND_TRAFFIC => $user->getAttribute(User::FIELD_REMIND_TRAFFIC),
            User::FIELD_REMIND_EXPIRE => $user->getAttribute(User::FIELD_REMIND_EXPIRE),
            User::FIELD_EXPIRED_AT => $user->getAttribute(User::FIELD_EXPIRED_AT),
            User::FIELD_BALANCE => $user->getAttribute(User::FIELD_BALANCE),
            User::FIELD_COMMISSION_BALANCE => $user->getAttribute(User::FIELD_COMMISSION_BALANCE),
            User::FIELD_PLAN_ID => $user->getAttribute(User::FIELD_PLAN_ID),
            User::FIELD_DISCOUNT => $user->getAttribute(User::FIELD_DISCOUNT),
            User::FIELD_COMMISSION_RATE => $user->getAttribute(User::FIELD_COMMISSION_RATE),
            User::FIELD_TELEGRAM_ID => $user->getAttribute(User::FIELD_TELEGRAM_ID),
            'avatar_url' => 'https://cdn.v2ex.com/gravatar/' . md5($user->getAttribute(User::FIELD_EMAIL)) . '?s=64&d=identicon'
        ];

        return response([
            'data' => $data
        ]);
    }

    /**
     * stat
     *
     * @param Request $request
     * @return ResponseFactory|Response
     */
    public function stat(Request $request)
    {
        $sessionId = $request->session()->get('id');
        /**
         * @var User $user
         */
        $user = User::find($sessionId);
        if ($user == NULL) {
            abort(500, __('user.user.info.user_not_exist'));
        }

        $stat = [
            $user->countUnpaidOrders(),
            $user->countUnprocessedTickets(),
            $user->countInvitedUsers()
        ];

        return response([
            'data' => $stat
        ]);
    }

    /**
     * subscribe
     *
     * @param Request $request
     * @return ResponseFactory|Response
     */
    public function subscribe(Request $request)
    {
        $sessionId = $request->session()->get('id');
        /**
         * @var User $user
         */
        $user = User::find($sessionId);

        if ($user == null) {
            abort(500, __('The user does not exist'));
        }

        if ($user->getAttribute(User::FIELD_PLAN_ID) > 0) {
            if ($user->plan() === null) {
                abort(500, __('Subscription plan does not exist'));
            }
        }
        $subscribeUrl = Helper::getSubscribeHost() . "/api/v1/client/subscribe?token={$user->getAttribute(User::FIELD_TOKEN)}";

        $data = [
            "subscribe_url" => $subscribeUrl,
            "plan" => $user->plan(),
            'reset_day' => $user->getResetDay(),
            User::FIELD_ID => $user->getKey(),
            User::FIELD_PLAN_ID => $user->getAttribute(User::FIELD_PLAN_ID),
            User::FIELD_TOKEN => $user->getAttribute(User::FIELD_TOKEN),
            User::FIELD_EXPIRED_AT => $user->getAttribute(User::FIELD_EXPIRED_AT),
            User::FIELD_U => $user->getAttribute(User::FIELD_U),
            User::FIELD_D => $user->getAttribute(User::FIELD_D),
            User::FIELD_TRANSFER_ENABLE => $user->getAttribute(User::FIELD_TRANSFER_ENABLE),
            User::FIELD_EMAIL => $user->getAttribute(User::FIELD_EMAIL),
        ];

        return response([
            "data" => $data
        ]);
    }

    /**
     * resetSecurity
     *
     * @param Request $request
     * @return ResponseFactory|Response
     */
    public function resetSecurity(Request $request)
    {
        $sessionId = $request->session()->get('id');
        /**
         * @var User $user
         */
        $user = User::find($sessionId);
        if ($user == null) {
            abort(500, __('The user does not exist'));
        }

        $user->setAttribute(User::FIELD_UUID, Helper::guid(true));
        $user->setAttribute(User::FIELD_TOKEN, Helper::guid());

        if (!$user->save()) {
            abort(500, __('Reset failed'));
        }

        return response([
            'data' => config('v2board.subscribe_url', config('v2board.app_url', env('APP_URL'))) . '/api/v1/client/subscribe?token=' . $user->getAttribute(User::FIELD_TOKEN)
        ]);
    }

    /**
     * update
     *
     * @param UserUpdate $request
     * @return ResponseFactory|Response
     */
    public function update(UserUpdate $request)
    {
        $sessionId = $request->session()->get('id');
        $reqRemindExpire = $request->input("remind_expire");
        $reqRemindTraffic = $request->input("remind_traffic");
        /**
         * @var User $user
         */
        $user = User::find($sessionId);
        if ($user == null) {
            abort(500, __('The user does not exist'));
        }

        if ($reqRemindExpire !== null) {
            $user->setAttribute(User::FIELD_REMIND_EXPIRE, (int)$reqRemindExpire);
        }

        if ($reqRemindTraffic !== null) {
            $user->setAttribute(User::FIELD_REMIND_TRAFFIC, (int)$reqRemindTraffic);
        }

        if (!$user->save()) {
            abort(500, __('Save failed'));
        }

        return response([
            'data' => true
        ]);
    }

    /**
     * transfer
     *
     * @param UserTransfer $request
     * @return ResponseFactory|Response
     */
    public function transfer(UserTransfer $request)
    {
        $sessionId = $request->session()->get('id');
        $reqTransferAmount = $request->input('transfer_amount');
        /**
         * @var User $user
         */
        $user = User::find($sessionId);
        if ($user == null) {
            abort(500, __('The user does not exist'));
        }

        if ($reqTransferAmount > $user->getAttribute(User::FIELD_COMMISSION_BALANCE)) {
            abort(500, __('Insufficient commission balance'));
        }

        $user->setAttribute(User::FIELD_COMMISSION_BALANCE, $user->getAttribute(User::FIELD_COMMISSION_BALANCE) - $reqTransferAmount);
        $user->setAttribute(User::FIELD_BALANCE, $user->getAttribute(User::FIELD_BALANCE) + $reqTransferAmount);

        if (!$user->save()) {
            abort(500, __('Transfer failed'));
        }
        return response([
            'data' => true
        ]);
    }


    public function getQuickLoginUrl(Request $request)
    {
        $user = User::find($request->session()->get('id'));
        if (!$user) {
            abort(500, __('The user does not exist'));
        }

        $code = Helper::guid();
        $key = CacheKey::get('TEMP_TOKEN', $code);
        Cache::put($key, $user->id, 60);
        $redirect = '/#/login?verify=' . $code . '&redirect=' . ($request->input('redirect') ? $request->input('redirect') : 'dashboard');
        if (config('v2board.app_url')) {
            $url = config('v2board.app_url') . $redirect;
        } else {
            $url = url($redirect);
        }
        return response([
            'data' => $url
        ]);
    }

}
