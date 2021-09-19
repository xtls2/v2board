<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\Serialize;

class ServerStat extends Model
{
    use Serialize;
    const FIELD_ID = "id";
    const FIELD_SERVER_ID = "server_id";
    const FIELD_SERVER_TYPE = "server_type";
    const FIELD_U = "u";
    const FIELD_D = "d";
    const FIELD_RECORD_TYPE = "record_type";
    const FIELD_RECORD_AT = "record_at";
    const FIELD_CREATED_AT = "created_at";
    const FIELD_UPDATED_AT = "updated_at";

    const RECORD_TYPE_DAY = 'd';
    const RECORD_TYPE_MONTH = 'm';

    protected $table = 'server_stat';
    protected $dateFormat = 'U';


    protected $casts = [
        self::FIELD_CREATED_AT => 'timestamp',
        self::FIELD_UPDATED_AT => 'timestamp'
    ];
}
