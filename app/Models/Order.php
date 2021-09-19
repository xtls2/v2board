<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Traits\Serialize;
use App\Models\Exceptions\OrderException;
use App\Models\Exceptions\CouponException;
use Illuminate\Support\Facades\DB;

/**
 * @property mixed created_at
 */
class Order extends Model
{
    use Serialize;
    const FIELD_ID = "id";
    const FIELD_INVITE_USER_ID = "invite_user_id";
    const FIELD_USER_ID = "user_id";
    const FIELD_PLAN_ID = "plan_id";
    const FIELD_COUPON_ID = "coupon_id";
    const FIELD_PAYMENT_ID = "payment_id";
    const FIELD_TYPE = "type";
    const FIELD_CYCLE = "cycle";
    const FIELD_TRADE_NO = "trade_no";
    const FIELD_CALLBACK_NO = "callback_no";
    const FIELD_TOTAL_AMOUNT = "total_amount"; //总金额
    const FIELD_DISCOUNT_AMOUNT = "discount_amount"; //折扣金额
    const FIELD_REFUND_AMOUNT = "refund_amount";  //退款金额
    const FIELD_BALANCE_AMOUNT = "balance_amount"; // 余额
    const FIELD_SURPLUS_AMOUNT = "surplus_amount"; //剩余价值
    const FIELD_SURPLUS_ORDER_IDS = "surplus_order_ids";
    const FIELD_STATUS = "status";
    const FIELD_COMMISSION_STATUS = "commission_status"; //佣金状态
    const FIELD_COMMISSION_BALANCE = "commission_balance";   //佣金余额
    const FIELD_PAID_AT = "paid_at";
    const FIELD_CREATED_AT = "created_at";
    const FIELD_UPDATED_AT = "updated_at";

    const CALLBACK_NO_MANUAL_OPERATION = "manual_operation";


    //0待支付1开通中2已取消3已完成4已折抵
    const STATUS_UNPAID = 0;
    const STATUS_PENDING = 1;
    const STATUS_CANCELLED = 2;
    const STATUS_COMPLETED = 3;
    const STATUS_DISCOUNTED = 4;

    //新购2续费3升级4重置流量包
    const TYPE_NEW_ORDER = 1;
    const TYPE_RENEW = 2;
    const TYPE_UPGRADE = 3;
    const TYPE_RESET_PRICE = 4;

    //一次性, 重置流量包
    const CYCLE_ONETIME = Plan::FIELD_ONETIME_PRICE;
    const CYCLE_RESET_PRICE = Plan::FIELD_RESET_PRICE;


    //0待确认1发放中2有效3无效
    const COMMISSION_STATUS_NEW = 0;
    const COMMISSION_STATUS_PENDING = 1;
    const COMMISSION_STATUS_VALID = 2;
    const COMMISSION_STATUS_INVALID = 3;

    protected $table = 'order';
    protected $dateFormat = 'U';

    protected $casts = [
        self::FIELD_CREATED_AT => 'timestamp',
        self::FIELD_UPDATED_AT => 'timestamp',
        self::FIELD_SURPLUS_ORDER_IDS => 'array'
    ];

    /**
     * 用户
     *
     * @return BelongsTo|Model|object
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User')->first();
    }

    /**
     * Plan
     *
     * @return BelongsTo|Model|object
     */
    public function plan()
    {
        return $this->belongsTo("App\Models\Plan")->first();
    }

    /**
     * Coupon
     *
     * @return BelongsTo|Model|object|null
     */
    public function coupon()
    {
        return $this->belongsTo("App\Models\Coupon")->first();
    }

    /**
     * Payment
     *
     * @return BelongsTo|Model|object|null
     */
    public function payment()
    {
        return $this->belongsTo("App\Models\Payment")->first();
    }


    /**
     * check expired
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        $month = Plan::STR_TO_TIME[$this->getAttribute(self::FIELD_CYCLE)];
        /**
         * @var Carbon $orderCreatedAt
         */
        $orderCreatedAt = $this->getAttribute(self::FIELD_CREATED_AT);
        $orderExpireDay = strtotime('+' . $month . ' month', $orderCreatedAt);
        return $orderExpireDay < time();
    }


    public function isNewOrder(): bool
    {
        return $this->getAttribute(Order::FIELD_TYPE) === Order::TYPE_NEW_ORDER;
    }

    /**
     * set user discount
     *
     * @param User $user
     *
     * @return void
     */
    public function setUserDiscount(User $user)
    {
        $discountAmount = $this->getAttribute(self::FIELD_DISCOUNT_AMOUNT);
        $totalAmount = $this->getAttribute(self::FIELD_TOTAL_AMOUNT);
        $userDiscount = $user->getAttribute(User::FIELD_DISCOUNT);
        if ($userDiscount > 0) {
            $this->setAttribute(self::FIELD_DISCOUNT_AMOUNT, $discountAmount + ($totalAmount * ($userDiscount / 100)));
        }
        $this->setAttribute(self::FIELD_TOTAL_AMOUNT, ($totalAmount - $this->getAttribute(self::FIELD_DISCOUNT_AMOUNT)));
    }


    /**
     * set order type
     *
     * @param User $user
     *
     * @return void
     */
    public function setOrderType(User $user)
    {
        $cycle = $this->getAttribute(self::FIELD_CYCLE);
        $userPlanId = (int)$user->getAttribute(User::FIELD_PLAN_ID);
        $userExpiredAt = (int)$user->getAttribute(User::FIELD_EXPIRED_AT);
        $planId = (int)$this->getAttribute(Order::FIELD_PLAN_ID);

        if ($cycle === self::CYCLE_RESET_PRICE) {
            $type = self::TYPE_RESET_PRICE;
        } else if ($userPlanId == 0 && $planId !== $userPlanId && ($userExpiredAt > time() || $userExpiredAt === 0)) {
            $type = self::TYPE_UPGRADE;
        } else if ($userExpiredAt > time() && $planId == $userPlanId) { // 用户订阅未过期且购买订阅与当前订阅相同 === 续费
            $type = self::TYPE_RENEW;
        } else { // 新购
            $type = self::TYPE_NEW_ORDER;
        }
        $this->setAttribute(self::FIELD_TYPE, $type);
    }

    /**
     * set upgrade value
     *
     * @return void
     */
    public function setUpgradeValue()
    {
        $surplusAmount = $this->getAttribute(self::FIELD_SURPLUS_AMOUNT);
        $totalAmount = $this->getAttribute(self::FIELD_TOTAL_AMOUNT);
        if ($surplusAmount >= $totalAmount) {
            $this->setAttribute(self::FIELD_REFUND_AMOUNT, $surplusAmount - $totalAmount);
            $this->setAttribute(self::FIELD_TOTAL_AMOUNT, 0);
        } else {
            $this->setAttribute(self::FIELD_TOTAL_AMOUNT, $totalAmount - $surplusAmount);
        }
    }


    /**
     * set invite
     *
     * @param User $user
     * @param bool $commissionFirstTimeEnable
     * @param int $commissionRate
     *
     * @return void
     */
    public function setInvite(User $user, bool $commissionFirstTimeEnable = true, int $commissionRate = 10)
    {
        $userInviteId = (int)$user->getAttribute(User::FIELD_INVITE_USER_ID);
        $totalAmount = (int)$this->getAttribute(self::FIELD_TOTAL_AMOUNT);

        if ($userInviteId > 0 && $totalAmount > 0) {
            $this->setAttribute(self::FIELD_INVITE_USER_ID, $userInviteId);
            $isCommission = false;
            switch ($user->getAttribute(User::FIELD_COMMISSION_TYPE)) {
                case User::COMMISSION_TYPE_SYSTEM:
                    $isCommission = (!$commissionFirstTimeEnable || $user->countValidOrders() == 0);
                    break;
                case User::COMMISSION_TYPE_CYCLE:
                    $isCommission = true;
                    break;
                case User::COMMISSION_TYPE_ONETIME:
                    $isCommission = $user->countValidOrders() == 0;
                    break;
            }

            if ($isCommission) {
                $inviter = User::find($userInviteId);
                $totalAmount = $this->getAttribute(Order::FIELD_TOTAL_AMOUNT);
                /**
                 * @var User $inviter
                 */
                if ($inviter && $inviter->getAttribute(User::FIELD_COMMISSION_RATE)) {
                    $commissionBalance = $totalAmount * ($inviter->getAttribute(User::FIELD_COMMISSION_RATE) / 100);
                } else {
                    $commissionBalance = $totalAmount * ($commissionRate / 100);
                }
                $this->setAttribute(self::FIELD_COMMISSION_BALANCE, $commissionBalance);
            }

        }
    }

    /**
     * set surplus Value
     *
     * @param User $user
     *
     * @return void
     */
    public function setSurplusValue(User $user)
    {
        $userExpiredAt = (int)$user->getAttribute(User::FIELD_EXPIRED_AT);

        if ($userExpiredAt === 0) {
            $this->_setSurplusValueByOneTime($user);
        } else {
            $this->_setSurplusValueByCycle($user);
        }
    }

    /**
     * set surplus Value by one time
     *
     * @param User $user
     *
     * @return void
     */
    private function _setSurplusValueByOneTime(User $user)
    {
        $planId = (int)$user->getAttribute(User::FIELD_PLAN_ID);
        if ($planId === 0) {
            return;
        }

        $plan = Plan::find($planId);
        if ($plan === null) {
            return;
        }

        $planTransferEnable = (int)$plan->getAttribute(Plan::FIELD_TRANSFER_ENABLE);
        $planOneTimePrice = (int)$plan->getAttribute(Plan::FIELD_ONETIME_PRICE);
        $userDiscount = (int)$user->getAttribute(User::FIELD_DISCOUNT);
        $userU = (int)$user->getAttribute(User::FIELD_U);
        $userD = (int)$user->getAttribute(User::FIELD_D);
        if ($planTransferEnable === 0) {
            return;
        }

        $trafficUnitPrice = $planOneTimePrice / $planTransferEnable;
        if ($userDiscount > 0) {
            $trafficUnitPrice = $trafficUnitPrice - ($trafficUnitPrice * $userDiscount / 100);
        }

        $notUsedTraffic = $planTransferEnable - (($userU + $userD) / 1073741824);
        $result = $trafficUnitPrice * $notUsedTraffic;

        $orders = $user->findCompletedNotResetPriceTypeOrders();

        $this->setAttribute(self::FIELD_DISCOUNT_AMOUNT, $result > 0 ? $result : 0);
        $this->setAttribute(self::FIELD_SURPLUS_ORDER_IDS, array_column($orders->toArray(), self::FIELD_ID));
    }

    /**
     * set surplus Value by cycle
     *
     * @param $user
     *
     * @return void
     */
    private function _setSurplusValueByCycle($user)
    {
        $orders = $user->findCompletedNotResetPriceTypeOrders();
        $orderSurplusMonth = 0;
        $orderSurplusAmounts = 0;
        $userExpiredAt = $user->getAttribute(User::FIELD_EXPIRED_AT);
        $userSurplusMonth = ($userExpiredAt - time()) / 2678400;
        foreach ($orders as $order) {
            /**
             * @var Order $order
             */
            // 兼容历史余留问题
            if ($order->getAttribute(self::FIELD_CYCLE) === self::CYCLE_RESET_PRICE) {
                continue;
            }

            if ($order->isExpired()) {
                continue;
            }

            $orderCycle = $order->getAttribute(Order::FIELD_CYCLE);
            $orderTotalAmount = $order->getAttribute(self::FIELD_TOTAL_AMOUNT);
            $orderBalanceAmount = $order->getAttribute(self::FIELD_BALANCE_AMOUNT);
            $orderSurplusAmount = $order->getAttribute(self::FIELD_SURPLUS_AMOUNT);
            $orderRefundAmount = $order->getAttribute(self::FIELD_REFUND_AMOUNT);
            $orderSurplusMonth = $orderSurplusMonth + Plan::STR_TO_TIME[$orderCycle];
            $orderSurplusAmounts = $orderSurplusAmounts + ($orderTotalAmount + $orderBalanceAmount + $orderSurplusAmount - $orderRefundAmount);
        }
        if ($orderSurplusMonth == 0 || $orderSurplusAmounts == 0) {
            return;
        }

        $monthUnitPrice = $orderSurplusAmounts / $orderSurplusMonth;
        // 如果用户过期月大于订单过期月
        if ($userSurplusMonth > $orderSurplusMonth) {
            $orderSurplusAmount = $orderSurplusMonth * $monthUnitPrice;
        } else {
            $orderSurplusAmount = $userSurplusMonth * $monthUnitPrice;
        }

        if ($orderSurplusAmount > 0) {
            $this->setAttribute(self::FIELD_SURPLUS_AMOUNT, $orderSurplusAmount);
            $this->setAttribute(self::FIELD_SURPLUS_ORDER_IDS, array_column($orders->toArray(),
                self::FIELD_ID));
        }
    }

    /**
     * stat month income
     *
     * @return mixed
     */
    public static function sumMonthIncome()
    {
        return self::where(self::FIELD_CREATED_AT, '>=', strtotime(date('Y-m-1')))
            ->where(self::FIELD_CREATED_AT, '<', time())
            ->whereNotIn(self::FIELD_STATUS, [self::STATUS_UNPAID, self::STATUS_CANCELLED])
            ->sum(self::FIELD_TOTAL_AMOUNT);
    }


    /**
     * stat day income
     *
     * @return mixed
     */
    public static function sumDayIncome()
    {
        return self::where(self::FIELD_CREATED_AT, '>=', strtotime(date('Y-m-d')))
            ->where(self::FIELD_CREATED_AT, '<', time())
            ->whereNotIn(self::FIELD_STATUS, [self::STATUS_UNPAID, self::STATUS_CANCELLED])
            ->sum(self::FIELD_TOTAL_AMOUNT);
    }


    /**
     * stat last month income
     *
     * @return mixed
     */
    public static function sumLastMonthIncome()
    {
        return Order::where(self::FIELD_CREATED_AT, '>=', strtotime('-1 month', strtotime(date('Y-m-1'))))
            ->where(self::FIELD_CREATED_AT, '<', strtotime(date('Y-m-1')))
            ->whereNotIn(self::FIELD_STATUS, [self::STATUS_UNPAID, self::STATUS_CANCELLED])
            ->sum(self::FIELD_TOTAL_AMOUNT);
    }


    /**
     * stat commission pending
     *
     * @return mixed
     */
    public static function countCommissionPending()
    {
        return self::where(self::FIELD_COMMISSION_STATUS, self::COMMISSION_STATUS_NEW)
            ->where(self::FIELD_INVITE_USER_ID, '!=', 0)
            ->whereNotIn(self::FIELD_STATUS, [self::STATUS_UNPAID, self::STATUS_CANCELLED])
            ->where(self::FIELD_COMMISSION_STATUS, '>', 0)
            ->count();
    }

    /**
     * find order by tradeNo
     *
     * @param string $tradeNo
     * @return mixed
     */
    public static function findByTradeNo(string $tradeNo)
    {
        return self::where(self::FIELD_TRADE_NO, $tradeNo)->first();
    }

    /**
     * use Coupon
     *
     * @param string $couponCode
     * @return int|mixed
     * @throws CouponException
     */
    public function useCoupon(string $couponCode)
    {
        $planId = $this->getAttribute(Order::FIELD_PLAN_ID);
        $coupon = Coupon::checkCode($couponCode, $planId);
        $couponType = $coupon->getAttribute(Coupon::FIELD_TYPE);
        $couponValue = $coupon->getAttribute(Coupon::FIELD_VALUE);
        $couponLimitUse = $coupon->getAttribute(Coupon::FIELD_LIMIT_USE);

        switch ($couponType) {
            case 1:
                $this->setAttribute(Order::FIELD_DISCOUNT_AMOUNT, $couponValue);
                break;
            case 2:
                $totalAmount = $this->getAttribute(Order::FIELD_TOTAL_AMOUNT);
                $this->setAttribute(Order::FIELD_DISCOUNT_AMOUNT, $totalAmount * ($couponValue / 100));
                break;
        }

        if ($couponLimitUse > 0) {
            $coupon->setAttribute(Coupon::FIELD_LIMIT_USE, $couponLimitUse - 1);
        }
        return $coupon->save() ? $coupon->getKey(): 0;
    }


    /**
     * @throws OrderException
     */
    public function cancel(): bool
    {
        /**
         * @var User $user
         */
        $user = $this->user();
        if ($user === null) {
            throw new OrderException("user not exist", 1);
        }

        DB::beginTransaction();
        $balanceAmount = $this->getAttribute(Order::FIELD_BALANCE_AMOUNT);
        if ($balanceAmount > 0) {
            $user->addBalance($balanceAmount);
            if (!$user->save()) {
                DB::rollBack();
                throw new OrderException("user save failed, rollback: " . $user->getKey(), 2);
            }
        }

        $this->setAttribute(Order::FIELD_STATUS, Order::STATUS_CANCELLED);
        if (!$this->save()) {
            DB::rollBack();
            throw new OrderException("order save failed, rollback: " . $this->getKey(), 3);
        }
        DB::commit();

        return true;
    }


    /**
     * @throws OrderException
     */
    public function open() : bool
    {
        /**
         * @var User $user
         */
        $user = $this->user();
        if ($user === null) {
            throw new OrderException("user not exist", 1);
        }
        /**
         * @var Plan $plan
         */
        $plan = $this->plan();
        if ($plan === null) {
            throw new OrderException("plan not found, break: " . $this->getAttribute(Order::FIELD_PLAN_ID), 4);
        }

        if ($this->getAttribute(Order::FIELD_REFUND_AMOUNT > 0)) {
            $user->setAttribute(User::FIELD_BALANCE, ($user->getAttribute(User::FIELD_BALANCE) +
                $this->getAttribute(Order::FIELD_REFUND_AMOUNT)));
        }

        $surplusOrderIds = $this->getAttribute(Order::FIELD_SURPLUS_ORDER_IDS);
        if (is_array($surplusOrderIds)) {
            try {
                Order::whereIn(Order::FIELD_ID, $surplusOrderIds)->update([
                    Order::FIELD_STATUS => Order::STATUS_DISCOUNTED
                ]);
            } catch (\Exception $e) {
                DB::rollback();
                throw new OrderException($e->getMessage(), 5);
            }
        }

        $orderCycle = $this->getAttribute(Order::FIELD_CYCLE);
        switch ($orderCycle) {
            case Order::CYCLE_ONETIME:
                $user->resetTraffic();
                $user->buyPlan($plan);
                break;
            case Order::CYCLE_RESET_PRICE:
                $user->resetTraffic();
                break;
            default:
                $orderType = $this->getAttribute(Order::FIELD_TYPE);
                $orderCycle = $this->getAttribute(Order::FIELD_CYCLE);
                $userExpiredAt = (int)$user->getAttribute(User::FIELD_EXPIRED_AT);

                if ($orderType === Order::TYPE_NEW_ORDER || $userExpiredAt === 0) {
                    $user->resetTraffic();
                } else if ($orderType == Order::TYPE_UPGRADE) {
                    $user->setAttribute(User::FIELD_EXPIRED_AT, time());
                }

                $user->buyPlan($plan, Plan::expiredTime($orderCycle, $user->getAttribute(User::FIELD_EXPIRED_AT)));
        }

        $orderType = $this->getAttribute(Order::FIELD_TYPE);
        switch ((int)$orderType) {
            case Order::TYPE_NEW_ORDER:
                if (config('v2board.new_order_event_id', 0)) {
                    $user->resetTraffic();
                }
                break;
            case Order::TYPE_RENEW:
                if (config('v2board.renew_order_event_id', 0)) {
                    $user->resetTraffic();
                }
                break;
            case Order::TYPE_UPGRADE:
                if (config('v2board.change_order_event_id', 0)) {
                    $user->resetTraffic();
                }
                break;
        }

        if (!$user->save()) {
            DB::rollBack();
            throw new OrderException("user saved failed, rollback: " . $user->getKey(), 2);
        }

        $this->setAttribute(Order::FIELD_STATUS, Order::STATUS_COMPLETED);
        if (!$this->save()) {
            DB::rollBack();
            throw new OrderException("order save failed, rollback: " . $this->getKey(), 3);
        }

        DB::commit();
        return true;
    }




    /**
     * Get coupon used count
     *
     * @param $couponId
     * @param $userId
     * @return int
     */
    public static function getCouponUsedCount($couponId, $userId):int
    {
        return self::where(self::FIELD_COUPON_ID, $couponId)
            ->where(self::FIELD_USER_ID, $userId)
            ->whereNotIn(self::FIELD_STATUS, [self::STATUS_UNPAID, self::STATUS_CANCELLED])
            ->count();
    }
}







