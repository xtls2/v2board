<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\Serialize;

class Knowledge extends Model
{
    use Serialize;
    const FIELD_ID = "id";
    const FIELD_LANGUAGE = "language";
    const FIELD_CATEGORY = "category";
    const FIELD_TITLE = "title";
    const FIELD_BODY = "body";
    const FIELD_SORT = "sort";
    const FIELD_SHOW = "show";
    const FIELD_CREATED_AT = "created_at";
    const FIELD_UPDATED_AT = "updated_at";

    const SHOW_OFF = 0;
    const SHOW_ON = 1;

    protected $table = 'knowledge';
    protected $dateFormat = 'U';

    protected $casts = [
        self::FIELD_CREATED_AT => 'timestamp',
        self::FIELD_UPDATED_AT => 'timestamp'
    ];

}
