<?php

namespace App\Services;

use App\Jobs\SendEmailJob;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Utils\CacheKey;
use Illuminate\Support\Facades\Cache;

class MailService
{
    /**
     * 提醒用户流量
     * @param User $user
     *
     * @return void
     */
    public static function remindTraffic(User $user)
    {
        $remindTraffic = $user->getAttribute(User::FIELD_REMIND_TRAFFIC);
        if ($remindTraffic == 0) {
            return;
        }

        $u = $user->getAttribute(User::FIELD_U);
        $d = $user->getAttribute(User::FIELD_D);
        $transferEnable = $user->getAttribute(User::FIELD_TRANSFER_ENABLE);

        if (!self::_remindTrafficIsWarnValue(($u + $d), $transferEnable)) {
            return;
        }

        $userId = $user->getAttribute(User::FIELD_ID);
        $flag = CacheKey::get('LAST_SEND_EMAIL_REMIND_TRAFFIC', $userId);

        if (Cache::get($flag)) {
            return;
        }

        $cacheExpireTTL = 24 * 3600;
        if (!Cache::put($flag, 1, $cacheExpireTTL)) {
            return;
        }

        SendEmailJob::dispatch([
            'email' => $user->getAttribute(User::FIELD_EMAIL),
            'subject' => __('The traffic usage in :app_name has reached 80%', [
                'app_name' => config('v2board.app_name', 'V2board')
            ]),
            'template_name' => 'remindTraffic',
            'template_value' => [
                'name' => config('v2board.app_name', 'V2Board'),
                'url' => config('v2board.app_url')
            ]
        ]);
    }


    /**
     * remind expire
     *
     * @param User $user
     */
    public function remindExpire(User $user)
    {
        $userExpiredAt = $user->getAttribute(User::FIELD_EXPIRED_AT);
        if ($userExpiredAt !== NULL && ($userExpiredAt - 86400) < time() && $userExpiredAt > time()) {
            SendEmailJob::dispatch([
                'email' => $user->getAttribute(User::FIELD_EMAIL),
                'subject' => __('The service in :app_name is about to expire', [
                    'app_name' =>  config('v2board.app_name', 'V2board')
                ]),
                'template_name' => 'remindExpire',
                'template_value' => [
                    'name' => config('v2board.app_name', 'V2Board'),
                    'url' => config('v2board.app_url')
                ]
            ]);
        }
    }


    /**
     * sendmail notify
     *
     * @param Ticket $ticket
     * @param TicketMessage $ticketMessage
     *
     * @return void
     */
    public static function sendEmailNotify(Ticket $ticket, TicketMessage $ticketMessage)
    {
        $userId = $ticket->getAttribute(Ticket::FIELD_USER_ID);
        $user = User::find($userId);
        if ($user == null) {
            return;
        }

        $cacheKey = 'ticket_sendEmailNotify_' . $userId;
        $userEmail = $user->getAttribute(User::FIELD_EMAIL);

        if (!Cache::get($cacheKey)) {
            Cache::put($cacheKey, 1, 1800);
            SendEmailJob::dispatch([
                'email' => $userEmail,
                'subject' => '您在' . config('v2board.app_name', 'V2Board') . '的工单得到了回复',
                'template_name' => 'notify',
                'template_value' => [
                    'name' => config('v2board.app_name', 'V2Board'),
                    'url' => config('v2board.app_url'),
                    'content' => "主题：{$ticket->getAttribute(Ticket::FIELD_SUBJECT)}\r\n回复内容：{$ticketMessage->getAttribute(TicketMessage::FIELD_MESSAGE)}"
                ]
            ]);
        }
    }

    /**
     * 计算流量是否到了警戒值
     *
     * @param $ud
     * @param $transfer_enable
     * @return bool
     */
    private static function _remindTrafficIsWarnValue($ud, $transfer_enable): bool
    {
        if (!$ud) {
            return false;
        }

        if (!$transfer_enable) {
            return false;
        }

        $percentage = ($ud / $transfer_enable) * 100;

        if ($percentage < 80) {
            return false;
        }
        if ($percentage >= 100) return false;
        return true;
    }
}
