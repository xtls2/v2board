<?php

namespace App\Http\Controllers\Guest;

use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Services\MailService;
use App\Services\TelegramService;
use Exception;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Utils\Helper;
use Illuminate\Support\Facades\DB;
use StdClass;

class TelegramController extends Controller
{
    protected $msg;
    /**
     * @var TelegramService
     */
    private $_service;

    public function __construct(Request $request)
    {
        if ($request->input('access_token') !== md5(config('v2board.telegram_bot_token'))) {
            abort(500, 'authentication failed');
        }
        $token =  config('v2board.telegram_bot_token');
        $this->_service = new TelegramService($token);
    }

    public function webhook(Request $request)
    {
        $this->msg = $this->_getMessage($request->input());
        if (!$this->msg) {
            return;
        }
        try {
            switch ($this->msg->message_type) {
                case 'send':
                    $this->_fromSend();
                    break;
                case 'reply':
                    $this->_fromReply();
                    break;
            }
        } catch (Exception $e) {
            $this->_service->sendMessage($this->msg->chat_id, $e->getMessage());
        }
    }

    private function _fromSend()
    {
        switch ($this->msg->command) {
            case '/bind':
                $this->_bind();
                break;
            case '/traffic':
                $this->_traffic();
                break;
            case '/getlatesturl':
                $this->_getLatestUrl();
                break;
            case '/unbind':
                $this->_unbind();
                break;
            default:
                $this->_help();
        }
    }

    private function _fromReply()
    {
        // ticket
        if (preg_match("/[#](.*)/", $this->msg->reply_text, $match)) {
            $this->_replayTicket($match[1]);
        }
    }

    private function _getMessage(array $data)
    {
        if (!isset($data['message'])) {
            return false;
        }

        $obj = new StdClass();
        $obj->is_private = $data['message']['chat']['type'] === 'private';
        if (!isset($data['message']['text'])) {
            return false;
        }
        $text = explode(' ', $data['message']['text']);
        $obj->command = $text[0];
        $obj->args = array_slice($text, 1);
        $obj->chat_id = $data['message']['chat']['id'];
        $obj->message_id = $data['message']['message_id'];
        $obj->message_type = !isset($data['message']['reply_to_message']['text']) ? 'send' : 'reply';
        $obj->text = $data['message']['text'];
        if ($obj->message_type === 'reply') {
            $obj->reply_text = $data['message']['reply_to_message']['text'];
        }
        return $obj;
    }

    private function _bind()
    {
        $msg = $this->msg;
        if (!$msg->is_private) {
            return;
        }

        if (!isset($msg->args[0])) {
            abort(500, '??????????????????????????????????????????');
        }
        $subscribeUrl = $msg->args[0];
        $subscribeUrl = parse_url($subscribeUrl);
        parse_str($subscribeUrl['query'], $query);
        $token = $query['token'];
        if (!$token) {
            abort(500, '??????????????????');
        }

        /**
         * @var User $user
         */
        $user = User::findByToken($token);
        if ($user === null) {
            abort(500, '???????????????');
        }

        if ($user->getAttribute(User::FIELD_TELEGRAM_ID)) {
            abort(500, '????????????????????????Telegram??????');
        }

        $user->setAttribute(User::FIELD_TELEGRAM_ID, $msg->chat_id);
        if (!$user->save()) {
            abort(500, '????????????');
        }
        $this->_service->sendMessage($msg->chat_id, '????????????');
    }

    private function _unbind()
    {
        $msg = $this->msg;
        if (!$msg->is_private) {
            return;
        }
        /**??
         * @var User $user
         */
        $user = User::findByTelegramId($msg->chat_id);
        if ($user === null) {
            $this->_help();
            $this->_service->sendMessage($msg->chat_id, '??????????????????????????????????????????????????????', 'markdown');
            return;
        }
        $user->setAttribute(User::FIELD_TELEGRAM_ID, 0);
        if (!$user->save()) {
            abort(500, '????????????');
        }
        $this->_service->sendMessage($msg->chat_id, '????????????', 'markdown');
    }

    private function _help()
    {
        $msg = $this->msg;
        if (!$msg->is_private) return;
        $commands = [
            '/bind ???????????? - ????????????' . config('v2board.app_name', 'V2Board') . '??????',
            '/traffic - ??????????????????',
            '/getlatesturl - ???????????????' . config('v2board.app_name', 'V2Board') . '??????',
            '/unbind - ????????????'
        ];
        $text = implode(PHP_EOL, $commands);
        $this->_service->sendMessage($msg->chat_id, "??????????????????????????????????????????\n\n$text", 'markdown');
    }

    private function _traffic()
    {
        $msg = $this->msg;
        if (!$msg->is_private) {
            return;
        }
        /**
         * @var User $user
         */
        $user = User::where(User::FIELD_TELEGRAM_ID, $msg->chat_id)->first();
        if ($user === null) {
            $this->_help();
            $this->_service->sendMessage($msg->chat_id, '??????????????????????????????????????????????????????', 'markdown');
            return;
        }
        $transferEnable = Helper::trafficConvert($user->getAttribute(User::FIELD_TRANSFER_ENABLE));
        $up = Helper::trafficConvert($user->getAttribute(User::FIELD_U));
        $down = Helper::trafficConvert($user->getAttribute(User::FIELD_D));
        $remaining = Helper::trafficConvert($user->getAttribute(User::FIELD_TRANSFER_ENABLE) - ($user->getAttribute(User::FIELD_U) + $user->getAttribute(User::FIELD_D)));
        $text = "????????????????\n?????????????????????????????????????????????\n???????????????`$transferEnable`\n???????????????`$up`\n???????????????`$down`\n???????????????`$remaining`";
        $this->_service->sendMessage($msg->chat_id, $text, 'markdown');

    }

    private function _getLatestUrl()
    {
        $msg = $this->msg;
        $text = sprintf(
            "%s?????????????????????%s",
            config('v2board.app_name', 'V2Board'),
            config('v2board.app_url')
        );
        $this->_service->sendMessage($msg->chat_id, $text, 'markdown');
    }

    private function _replayTicket($ticketId)
    {
        $msg = $this->msg;
        if (!$msg->is_private) return;

        /**
         * @var User $user
         */
        $user = User::where(User::FIELD_TELEGRAM_ID, $msg->chat_id)->first();
        if ($user === null) {
            abort(500, '???????????????');
        }

        if ($user->isAdmin() || $user->isStaff()) {
            /**
             * @var Ticket $ticket
             */
            $ticket = Ticket::find($ticketId);
            if ($ticket == null) {
                abort(500, '???????????????');
            }

            if ($ticket->isClosed()) {
                abort(500, '??????????????????????????????');
            }
            DB::beginTransaction();
            $ticketMessage = new TicketMessage();
            $ticketMessage->setAttribute(TicketMessage::FIELD_USER_ID, $user->getKey());
            $ticketMessage->setAttribute(TicketMessage::FIELD_TICKET_ID, $ticket->getKey());
            $ticketMessage->setAttribute(TicketMessage::FIELD_MESSAGE, $msg->text);
            $ticket->setAttribute(Ticket::FIELD_LAST_REPLY_USER_ID, $user->getKey());

            if (!$ticketMessage->save() || !$ticket->save()) {
                DB::rollback();
                abort(500, '??????????????????');
            }
            DB::commit();
            MailService::sendEmailNotify($ticket, $ticketMessage);
        }

        if (!config('v2board.telegram_bot_enable', 0)) {
            return;
        }

        $this->_service->sendMessage($msg->chat_id, "#`$ticketId` ????????????????????????", 'markdown');
        TelegramService::sendMessageWithAdmin("#`{$ticketId}` ??????????????? {$user->email} ????????????", true);
    }

}
