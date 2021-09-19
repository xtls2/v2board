<?php

namespace App\Models;

use App\Models\Traits\Serialize;
use Illuminate\Database\Eloquent\Model;
use App\Models\Exceptions\CouponException;

class Coupon extends Model
{
    use Serialize;

    const FIELD_ID = "id";
    const FIELD_CODE = "code";
    const FIELD_NAME = "name";
    const FIELD_TYPE = "type";
    const FIELD_VALUE = "value";
    const FIELD_LIMIT_USE = "limit_use";
    const FIELD_LIMIT_PLAN_IDS = "limit_plan_ids";  //置顶订阅的IDS
    const FIELD_LIMIT_USE_WITH_USER = 'limit_use_with_user';
    const FIELD_STARTED_AT = "started_at";
    const FIELD_ENDED_AT = "ended_at";
    const FIELD_CREATED_AT = "created_at";
    const FIELD_UPDATED_AT = "updated_at";

    protected $table = 'coupon';
    protected $dateFormat = 'U';

    protected $casts = [
        self::FIELD_CREATED_AT => 'timestamp',
        self::FIELD_UPDATED_AT => 'timestamp',
        self::FIELD_LIMIT_PLAN_IDS => 'array'
    ];

    /**
     * exist code
     *
     * @param string $code
     * @return bool
     */
    public static function existCode(string $code): bool
    {
        return self::where(self::FIELD_CODE, $code)->count > 0;
    }


    /**
     * checkCode
     *
     * am string $code
     * @param int $planId
     * @param int $userId
     * @return Coupon
     * @throws CouponException
     */
    public static function checkCode(string $code, int $planId = 0, int $userId = 0): Coupon
    {
        /**
         * @var Coupon $coupon
         */
        $coupon = self::findByCode($code);
        if ($coupon == null) {
            throw new CouponException(__('Invalid coupon'), 1);
        }

        $limitUse = $coupon->getAttribute(Coupon::FIELD_LIMIT_USE);
        $startedAt = $coupon->getAttribute(Coupon::FIELD_STARTED_AT);
        $endAt = $coupon->getAttribute(Coupon::FIELD_ENDED_AT);
        $couponLimitPlanIds = $coupon->getAttribute(Coupon::FIELD_LIMIT_PLAN_IDS);
        $couponLimitUserWithUser = $coupon->getAttribute(Coupon::FIELD_LIMIT_USE_WITH_USER);

        if ($limitUse <= 0 && $limitUse !== NULL) {
            throw new CouponException(__('This coupon is no longer available'), 2);
        }

        if ($startedAt > time()) {
            throw new CouponException(__('This coupon has not yet started'), 3);
        }

        if ($endAt < time()) {
            throw new CouponException(__('This coupon has expired'), 4);
        }

        if ($couponLimitPlanIds) {
            if ($planId > 0 && !in_array($planId, $couponLimitPlanIds)) {
                throw new  CouponException(__('The coupon code cannot be used for this subscription'), 5);
            }
        }

        if ($couponLimitUserWithUser && $userId > 0) {
            $couponUsedCount = Order::getCouponUsedCount($coupon->getKey(), $userId);
            if ($couponUsedCount >= $couponLimitUserWithUser) {
                throw new CouponException(__('The coupon can only be used :limit_use_with_user per person', [
                    'limit_use_with_user' => $couponLimitUserWithUser
                ]), 6);
            }
        }
        return $coupon;
    }

    /**
     * find coupon by code
     *
     * @param string $code
     * @return mixed
     */
    public static function findByCode(string $code)
    {
        return self::where(self::FIELD_CODE, $code)->first();
    }
}
