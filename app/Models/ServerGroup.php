<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class ServerGroup extends Model
{
    const FIELD_ID = "id";
    const FIELD_NAME = "name";
    const FIELD_CREATED_AT = "created_at";
    const FIELD_UPDATED_AT = "updated_at";

    protected $table = 'server_group';
    protected $dateFormat = 'U';

    protected $casts = [
        self::FIELD_CREATED_AT => 'timestamp',
        self::FIELD_UPDATED_AT => 'timestamp'
    ];
}
