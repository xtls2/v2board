<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\Serialize;

class TicketMessage extends Model
{
    use Serialize;
    const FIELD_ID = "id";
    const FIELD_USER_ID = "user_id";
    const FIELD_TICKET_ID = "ticket_id";
    const FIELD_MESSAGE = "message";
    const FIELD_CREATED_AT = "created_at";
    const FIELD_UPDATED_AT = "updated_at";

    protected $table = 'ticket_message';
    protected $dateFormat = 'U';

    protected $casts = [
        self::FIELD_CREATED_AT => 'timestamp',
        self::FIELD_UPDATED_AT => 'timestamp'
    ];
}
