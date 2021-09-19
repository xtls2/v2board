<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\Serialize;

class MailLog extends Model
{
    use Serialize;
    const FIELD_ID = "id";
    const FIELD_EMAIL = "email";
    const FIELD_SUBJECT = "subject";
    const FIELD_TEMPLATE_NAME = "template_name";
    const FIELD_ERROR = "error";
    const FIELD_CREATED_AT = "created_at";
    const FIELD_UPDATED_AT = "updated_at";

    protected $table = 'mail_log';
    protected $dateFormat = 'U';

    protected $casts = [
        self::FIELD_CREATED_AT => 'timestamp',
        self::FIELD_UPDATED_AT => 'timestamp'
    ];

}
