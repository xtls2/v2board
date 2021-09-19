<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\Serialize;


class Payment extends Model
{
    use Serialize;
    const FIELD_ID = "id";
    const FIELD_UUID = "uuid";
    const FIELD_PAYMENT = "payment";
    const FIELD_NAME = "name";
    const FIELD_CONFIG = "config";
    const FIELD_ENABLE = "enable";
    const FIELD_SORT = "sort";
    const FIELD_CREATED_AT = "created_at";
    const FIELD_UPDATED_AT = "updated_at";

    const PAYMENT_ON = 1;
    const PAYMENT_OFF = 0;

    protected $table = 'payment';
    protected $dateFormat = 'U';

    protected $casts = [
        self::FIELD_CREATED_AT => 'timestamp',
        self::FIELD_UPDATED_AT=> 'timestamp',
        self::FIELD_CONFIG => 'array'
    ];


    /**
     * check enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return (bool)$this->getAttribute(self::FIELD_ENABLE);
    }

    /**
     * find by uuid
     *
     * @param string $uuid
     * @return mixed
     */
    public static function findByUUID(string $uuid)
    {
        return self::where(self::FIELD_UUID, $uuid)->first();
    }
}
