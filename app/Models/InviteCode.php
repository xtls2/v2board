<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\Serialize;

class InviteCode extends Model
{
    use Serialize;

    const FIELD_ID = "id";
    const FIELD_USER_ID = "user_id";
    const FIELD_CODE = "code";
    const FIELD_STATUS = "status";
    const FIELD_PV = "pv";
    const FIELD_CREATED_AT = "created_at";
    const FIELD_UPDATED_AT = "updated_at";

    const STATUS_UNUSED = 0;
    const STATUS_USED = 1;

    protected $table = 'invite_code';
    protected $dateFormat = 'U';

    protected $casts = [
        self::FIELD_CREATED_AT => 'timestamp',
        self::FIELD_UPDATED_AT => 'timestamp'
    ];



}
