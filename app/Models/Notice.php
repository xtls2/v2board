<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\Serialize;

class Notice extends Model
{
    use Serialize;
    const FIELD_ID = "id";
    const FIELD_TITLE = "title";
    const FIELD_CONTENT = "content";
    const FIELD_IMG_URL = "img_url";
    const FIELD_CREATED_AT = "created_at";
    const FIELD_UPDATED_AT = "updated_at";

    protected $table = 'notice';
    protected $dateFormat = 'U';

    protected $casts = [
        self::FIELD_CREATED_AT => 'timestamp',
        self::FIELD_UPDATED_AT => 'timestamp'
    ];

}
