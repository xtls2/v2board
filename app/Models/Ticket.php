<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\Serialize;

class Ticket extends Model
{
    use Serialize;
    const FIELD_ID = "id";
    const FIELD_USER_ID = "user_id";
    const FIELD_LAST_REPLY_USER_ID = "last_reply_user_id";  //上次答复的用户ID
    const FIELD_SUBJECT = "subject";
    const FIELD_LEVEL = "level";
    const FIELD_STATUS = "status";
    const FIELD_CREATED_AT = "created_at";
    const FIELD_UPDATED_AT = "updated_at";
    const STATUS_UNPROCESSED = 0;

    const LEVEL_LOW = 0;
    const LEVEL_MEDIUM = 1;
    const LEVEL_HIGH = 2;

    const STATUS_OPEN = 0;
    const STATUS_CLOSE = 1;

    protected $table = 'ticket';
    protected $dateFormat = 'U';

    protected $casts = [
        self::FIELD_CREATED_AT => 'timestamp',
        self::FIELD_UPDATED_AT => 'timestamp'
    ];

    /**
     * ticket messages
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function messages()
    {
        return $this->hasMany("App\Models\TicketMessage", TicketMessage::FIELD_TICKET_ID);
    }

    /**
     * Get last ticketMessage
     *
     * @return TicketMessage
     */
    public function getLastMessage()
    {
        return TicketMessage::where(TicketMessage::FIELD_TICKET_ID, $this->getAttribute(self::FIELD_ID))->
        orderBy(TicketMessage::FIELD_ID, "DESC")->first();
    }


    /**
     * check closed
     *
     * @return bool
     */
    public function isClosed()
    {
        return $this->getAttribute(self::FIELD_STATUS) == self::STATUS_CLOSE;
    }


    /**
     * find first Ticket with id and userId
     *
     * @param int $id
     * @param int $userId
     *
     * @return Ticket
     */
    public static function findFirstByUserId($id, $userId)
    {
        return self::where([self::FIELD_ID => $id, self::FIELD_USER_ID => $userId])->first();
    }


    /**
     * find all Tickets and userId
     *
     * @param int $id
     * @param int $userId
     *
     * @return Ticket
     */
    public static function findAllByUserId($userId)
    {
        return self::where([self::FIELD_USER_ID => $userId])->
        orderBy(self::FIELD_CREATED_AT, "DESC")->get();
    }

    /**
     * stats ticket pending
     *
     * @return mixed
     */
    public static function countTicketPending()
    {
        return self::where(self::FIELD_STATUS, self::STATUS_OPEN)->count();
    }


}
