<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\Traits\Serialize;


class ServerLog extends Model
{
    use Serialize;
    const FIELD_ID = "id";
    const FIELD_USER_ID = "user_id";
    const FIELD_SERVER_ID = "server_id";
    const FIELD_U = "u";
    const FIELD_D = "d";
    const FIELD_RATE = "rate";
    const FIELD_METHOD = "method";
    const FIELD_LOG_AT = "log_at";
    const FIELD_CREATED_AT = "created_at";
    const FIELD_UPDATED_AT = "updated_at";

    protected $table = 'server_log';
    protected $dateFormat = 'U';

    protected $casts = [
        self::FIELD_CREATED_AT => 'timestamp',
        self::FIELD_UPDATED_AT => 'timestamp'
    ];


    /**
     * add traffic
     *
     * @param int $u
     * @param int $d
     * @return bool
     */
    public function addTraffic(int $u, int $d): bool
    {
        $this->setAttribute(User::FIELD_U, $this->getAttribute(User::FIELD_U) + $u);
        $this->setAttribute(User::FIELD_D, $this->getAttribute(User::FIELD_D) + $d);
        return true;
    }

}
