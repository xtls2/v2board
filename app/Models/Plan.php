<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\Serialize;


class Plan extends Model
{
    use Serialize;
    const FIELD_ID = "id";
    const FIELD_GROUP_ID = "group_id";
    const FIELD_TRANSFER_ENABLE = "transfer_enable";
    const FIELD_NAME = "name";
    const FIELD_SHOW = "show"; //销售状态
    const FIELD_SORT = "sort";
    const FIELD_RENEW = "renew";  //是否可续费
    const FIELD_CONTENT = "content";
    const FIELD_MONTH_PRICE = "month_price";
    const FIELD_QUARTER_PRICE = "quarter_price";
    const FIELD_HALF_YEAR_PRICE = "half_year_price";
    const FIELD_YEAR_PRICE = "year_price";
    const FIELD_TWO_YEAR_PRICE = "two_year_price";
    const FIELD_THREE_YEAR_PRICE = "three_year_price";
    const FIELD_ONETIME_PRICE = "onetime_price";  //一次性价格
    const FIELD_RESET_PRICE = "reset_price";   //重置流量价格
    const FIELD_CREATED_AT = "created_at";
    const FIELD_UPDATED_AT = "updated_at";

    const STR_TO_TIME = [
        self::FIELD_MONTH_PRICE => 1,
        self::FIELD_QUARTER_PRICE => 3,
        self::FIELD_HALF_YEAR_PRICE => 6,
        self::FIELD_YEAR_PRICE => 12,
        self::FIELD_TWO_YEAR_PRICE => 24,
        self::FIELD_THREE_YEAR_PRICE => 36
    ];

    const SHOW_OFF = 0;
    const SHOW_ON = 1;

    const RENEW_OFF = 0;
    const RENEW_ON = 1;


    protected $table = 'plan';
    protected $dateFormat = 'U';

    protected $casts = [
        self::FIELD_CREATED_AT => 'timestamp',
        self::FIELD_UPDATED_AT => 'timestamp'
    ];

    /**
     * users
     *
     * @return Collection
     */
    public function users(): Collection
    {
        return $this->hasMany("App\Models\User")->get();
    }

    /**
     * check show
     *
     * @return bool
     */
    public function isShowOn(): bool
    {
        return $this->getAttribute(self::FIELD_SHOW) == self::SHOW_ON;
    }


    /**
     * check renew
     *
     * @return bool
     */
    public function isRenewOn(): bool
    {
        return $this->getAttribute(self::FIELD_RENEW) == self::RENEW_ON;
    }


    /**
     * get show plans
     *
     * @return mixed
     */
    public static function getShowPlans()
    {
        return self::where(self::FIELD_SHOW, self::SHOW_ON)->orderBy(self::FIELD_SORT, "ASC")->get();
    }


    /**
     * 格式化时间
     *
     * @param string $cycle
     * @param mixed  $timestamp
     * @return false|int
     */
    public static function expiredTime(string $cycle, $timestamp = null)
    {
        if ($timestamp === null || $timestamp < time() ) {
            $timestamp = time();
        }
        switch ($cycle) {
            case Plan::FIELD_MONTH_PRICE:
                $time = strtotime('+1 month', $timestamp);
                break;
            case Plan::FIELD_QUARTER_PRICE:
                $time = strtotime('+3 month', $timestamp);
                break;
            case Plan::FIELD_HALF_YEAR_PRICE:
                $time = strtotime('+6 month', $timestamp);
                break;
            case Plan::FIELD_YEAR_PRICE:
                $time = strtotime('+12 month', $timestamp);
                break;
            case Plan::FIELD_TWO_YEAR_PRICE:
                $time = strtotime('+24 month', $timestamp);
                break;
            case Plan::FIELD_THREE_YEAR_PRICE:
                $time = strtotime('+36 month', $timestamp);
                break;
            default:
                $time = null;
        }
        return $time;
    }


}
