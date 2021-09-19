<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\Serialize;

class User extends Model
{
    use Serialize;
    const FIELD_ID = "id";
    const FIELD_INVITE_USER_ID = "invite_user_id";
    const FIELD_TELEGRAM_ID = "telegram_id";
    const FIELD_EMAIL = "email";
    const FIELD_PASSWORD = "password";
    const FIELD_PASSWORD_ALGO = "password_algo";
    const FIELD_PASSWORD_SALT = 'password_salt';
    const FIELD_BALANCE = "balance";
    const FIELD_DISCOUNT = "discount";
    const FIELD_COMMISSION_TYPE = "commission_type";
    const FIELD_COMMISSION_RATE = "commission_rate";
    const FIELD_COMMISSION_BALANCE = "commission_balance";
    const FIELD_T = "t";
    const FIELD_U = "u";
    const FIELD_D = "d";
    const FIELD_TRANSFER_ENABLE = "transfer_enable";
    const FIELD_BANNED = "banned";     //禁止
    const FIELD_IS_ADMIN = "is_admin";
    const FIELD_IS_STAFF = "is_staff";
    const FIELD_LAST_LOGIN_AT = "last_login_at";
    const FIELD_LAST_LOGIN_IP = "last_login_ip";
    const FIELD_UUID = "uuid";
    const FIELD_GROUP_ID = "group_id";
    const FIELD_PLAN_ID = "plan_id";
    const FIELD_REMIND_EXPIRE = "remind_expire"; //提醒过期
    const FIELD_REMIND_TRAFFIC = "remind_traffic";
    const FIELD_TOKEN = "token";
    const FIELD_REMARKS = "remarks";
    const FIELD_EXPIRED_AT = "expired_at";
    const FIELD_CREATED_AT = "created_at";
    const FIELD_UPDATED_AT = "updated_at";

    const BANNED_ON = 1;
    const BANNED_OFF = 0;

    const COMMISSION_TYPE_SYSTEM = 0;
    const COMMISSION_TYPE_CYCLE = 1;
    const COMMISSION_TYPE_ONETIME = 2;

    protected $table = 'user';
    protected $dateFormat = 'U';

    protected $casts = [
        self::FIELD_CREATED_AT => 'timestamp',
        self::FIELD_UPDATED_AT => 'timestamp'
    ];


    /**
     * get plan
     *
     * @return Model|BelongsTo|object|null
     */
    public function plan()
    {
        return $this->belongsTo("App\Models\Plan")->first();
    }


    /**
     * get unused invite codes
     *
     * @return mixed
     */
    public function getUnusedInviteCodes()
    {
        return InviteCode::where(InviteCode::FIELD_USER_ID, $this->getKey())->
        where(InviteCode::FIELD_STATUS, InviteCode::STATUS_UNUSED)->get();
    }


    /**
     * check available
     * @return bool
     */
    public function isAvailable(): bool
    {
        if ($this->isBanned()) {
            return false;
        }

        if ($this->isExpired()) {
            return false;
        }

        if (!$this->isTransferEnabled()) {
            return false;
        }


        return true;
    }

    /**
     * check user banned
     *
     * @return bool
     */
    public function isBanned(): bool
    {
        return (bool)$this->getAttribute(self::FIELD_BANNED) != 0;
    }

    /**
     * check user transfer enabled
     *
     * return bool
     */
    public function isTransferEnabled(): bool
    {
        return (bool)$this->getAttribute(self::FIELD_TRANSFER_ENABLE) > 0;
    }


    /**
     * check user expired
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        $expiredAt = $this->getAttribute(self::FIELD_EXPIRED_AT);
        if ($expiredAt > time() || !$expiredAt) {
            return false;
        }
        return true;
    }

    /**
     * check user not completed order
     *
     * @return bool
     */
    public function isNotCompletedOrders(): bool
    {
        return Order::whereIn(Order::FIELD_STATUS, [Order::STATUS_UNPAID, Order::STATUS_PENDING])->where(Order::FIELD_USER_ID,
                $this->getKey())->count() > 0;
    }

    /**
     * count valid orders
     *
     * @return int
     */
    public function countValidOrders(): int
    {
        return Order::where(Order::FIELD_USER_ID, $this->getKey())
            ->whereNotIn('status', [Order::STATUS_UNPAID, Order::STATUS_CANCELLED])
            ->count();
    }

    /**
     * check admin
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->getAttribute(self::FIELD_IS_ADMIN) != 0;
    }

    /**
     * check staff
     *
     * @return bool
     */
    public function isStaff(): bool
    {
        return $this->getAttribute(self::FIELD_IS_STAFF) != 0;
    }


    /**
     * stat commission balance
     *
     * @param int $orderStatus
     * @param int $commissionStatus
     *
     * @return int
     */
    public function statCommissionBalance(int $orderStatus, int $commissionStatus): int
    {
        return (int)Order::where(Order::FIELD_STATUS, $orderStatus)
            ->where(Order::FIELD_COMMISSION_STATUS, $commissionStatus)
            ->where(Order::FIELD_INVITE_USER_ID, $this->getKey())
            ->sum(Order::FIELD_COMMISSION_BALANCE);
    }


    /**
     * get invited order details
     *
     * @param int $orderStatus
     *
     * @return mixed
     */
    public function getInvitedOrderDetails(int $orderStatus)
    {
        return Order::where(Order::FIELD_INVITE_USER_ID, $this->getKey())
            ->select([
                Order::FIELD_ID,
                Order::FIELD_COMMISSION_BALANCE,
                Order::FIELD_COMMISSION_STATUS,
                Order::FIELD_CREATED_AT,
                Order::FIELD_UPDATED_AT
            ])
            ->where(Order::FIELD_COMMISSION_BALANCE, '>', 0)
            ->where(Order::FIELD_STATUS, $orderStatus)->get();

    }

    /**
     * reset traffic
     *
     * @return void
     */
    public function resetTraffic()
    {
        $this->setAttribute(User::FIELD_U, 0);
        $this->setAttribute(User::FIELD_D, 0);
    }

    /**
     * buy plan with onetime
     *
     * @param Plan $plan
     * @param int|null $expiredAt
     * @return void
     */
    public function buyPlan(Plan $plan, int $expiredAt = null)
    {
        $this->setAttribute(User::FIELD_TRANSFER_ENABLE, $plan->getAttribute(Plan::FIELD_TRANSFER_ENABLE) * 1073741824);
        $this->setAttribute(User::FIELD_PLAN_ID, $plan->getAttribute(Plan::FIELD_ID));
        $this->setAttribute(User::FIELD_GROUP_ID, $plan->getAttribute(Plan::FIELD_GROUP_ID));
        $this->setAttribute(User::FIELD_EXPIRED_AT, $expiredAt);
    }

    /**
     * add balance
     *
     * @param int $balance
     * @return bool
     */
    public function addBalance(int $balance): bool
    {
        $this->setAttribute(self::FIELD_BALANCE, ($this->getAttribute(self::FIELD_BALANCE) + $balance));
        return true;
    }


    /**
     * add traffic
     *
     * @param int $u
     * @param int $d
     * @return bool
     */
    public function addTraffic(int $u, int $d): bool
    {
        $this->setAttribute(User::FIELD_U, $this->getAttribute(User::FIELD_U) + $u);
        $this->setAttribute(User::FIELD_D, $this->getAttribute(User::FIELD_D) + $d);
        $this->setAttribute(User::FIELD_T, time());
        return true;
    }


    /**
     * gets the number of invited users
     *
     * @return int
     */
    public function countInvitedUsers(): int
    {
        return self::where(self::FIELD_INVITE_USER_ID, $this->getKey())->count();
    }


    /**
     * get the number of invite codes
     *
     * @return int
     */
    public function countUnusedInviteCodes(): int
    {
        return $this->hasMany("App\Models\InviteCode", InviteCode::FIELD_USER_ID)
            ->where(InviteCode::FIELD_STATUS, InviteCode::STATUS_UNUSED)->count();
    }


    /**
     * Get the number of unprocessed tickets
     *
     * @return int
     */
    public function countUnprocessedTickets(): int
    {
        return Ticket::where(Ticket::FIELD_STATUS, Ticket::STATUS_UNPROCESSED)
            ->where(Ticket::FIELD_USER_ID, $this->getKey())
            ->count();
    }


    /**
     * Get the number of Unpaid orders
     *
     * @return int
     */
    public function countUnpaidOrders(): int
    {
        return Order::where(Order::FIELD_STATUS, Order::STATUS_UNPAID)
            ->where(Order::FIELD_USER_ID, $this->getKey())
            ->count();
    }

    /**
     * Get reset day
     *
     * @return string|null
     */
    public function getResetDay(): ?string
    {
        $expiredAt = $this->getAttribute(self::FIELD_EXPIRED_AT);
        if ($expiredAt <= time() || $expiredAt === NULL) {
            return null;
        }

        $day = date('d', $expiredAt);
        $today = date('d');
        $lastDay = date('d', strtotime('last day of +0 months'));

        if ((int)config('v2board.reset_traffic_method') === 0) {
            return $lastDay - $today;
        }
        if ((int)config('v2board.reset_traffic_method') === 1) {
            if ((int)$day >= (int)$today && (int)$day >= (int)$lastDay) {
                return $lastDay - $today;
            }
            if ((int)$day >= (int)$today) {
                return $day - $today;
            } else {
                return $lastDay - $today + $day;
            }
        }
        return null;
    }


    /**
     * find completed not reset price Type Orders
     *
     * @return mixed
     */
    public function findCompletedNotResetPriceTypeOrders()
    {
        return Order::where(Order::FIELD_USER_ID, $this->getKey())->
        where(Order::FIELD_CYCLE, '!=', Order::CYCLE_RESET_PRICE)->where(Order::FIELD_STATUS, Order::STATUS_COMPLETED)->get();
    }


    /**
     * find admin users
     *
     * @param bool $includeStaff
     * @param bool $withTelegram
     *
     * @return mixed
     */
    public static function findAdminUsers(bool $includeStaff = true, bool $withTelegram = true)
    {
        $users = self::where(function ($query) use ($includeStaff) {
            $query->where('is_admin', 1);
            if ($includeStaff) {
                $query->orWhere('is_staff', $includeStaff);
            }
        });

        if ($withTelegram) {
            $users->where('telegram_id', '>', 0);
        }

        return $users->get();
    }

    /**
     * count Month Register
     *
     * @return mixed
     */
    public static function countMonthRegister()
    {
        return User::where(self::FIELD_CREATED_AT, '>=', strtotime(date('Y-m-1')))
            ->where(self::FIELD_CREATED_AT, '<', time())->count();
    }

    /**
     * count effective plan users
     *
     * @param $planId
     * @return mixed
     */
    public static function countEffectivePlanUsers($planId)
    {
        return self::where(self::FIELD_PLAN_ID, $planId)->where(self::FIELD_EXPIRED_AT, '>=', time())->count();
    }

    /**
     * find user by email
     *
     * @param string $email
     * @return mixed
     */
    public static function findByEmail(string $email)
    {
        return self::where(self::FIELD_EMAIL, $email)->first();
    }

    /**
     * find user by token
     *
     * @param string $token
     * @return mixed
     */
    public static function findByToken(string $token)
    {
        return self::where(self::FIELD_TOKEN, $token)->first();
    }


    /**
     * find user by telegram id
     *
     * @param int $telegramId
     * @return mixed
     */
    public static function findByTelegramId(int $telegramId)
    {
        return self::where(self::FIELD_TELEGRAM_ID, $telegramId)->first();
    }

}
