<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\Serialize;

class OrderStat extends Model
{
    use Serialize;
    const FIELD_ID = "id";
    const FIELD_ORDER_COUNT = "order_count";
    const FIELD_ORDER_AMOUNT = "order_amount";
    const FIELD_COMMISSION_COUNT = "commission_count";
    const FIELD_COMMISSION_AMOUNT = "commission_amount";
    const FIELD_RECORD_TYPE = "record_type";
    const FIELD_RECORD_AT = "record_at";
    const FIELD_CREATED_AT = "created_at";
    const FIELD_UPDATED_AT = "updated_at";


    const RECORD_TYPE_D = 'd'; //day;
    const RECORY_TYPE_M = 'm'; //month

    protected $table = 'order_stat';
    protected $dateFormat = 'U';

    protected $casts = [
        self::FIELD_CREATED_AT => 'timestamp',
        self::FIELD_UPDATED_AT => 'timestamp'
    ];
}
