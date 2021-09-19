<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\TicketSave;
use App\Http\Requests\User\TicketWithdraw;
use App\Jobs\SendTelegramJob;
use App\Models\User;
use App\Services\TelegramService;
use App\Utils\Dict;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    /**
     * fetch
     *
     * @param Request $request
     * @return ResponseFactory|Response
     */
    public function fetch(Request $request)
    {
        $sessionId = $request->session()->get('id');
        $reqId = (int)$request->input('id');
        if ($reqId > 0) {
            $ticket = Ticket::findFirstByUserId($reqId, $sessionId);
            if ($ticket == null) {
                abort(500, __('Ticket does not exist'));
            }

            $ticketMessages = $ticket->messages()->get();
            foreach ($ticketMessages as $message) {
                if ($message->getAttribute(TicketMessage::FIELD_USER_ID) == $sessionId) {
                    $message->setAttribute("is_me", true);
                } else {
                    $message->setAttribute("is_me", false);
                }
            }
            $ticket->setAttribute("message", $ticketMessages);
            $data = $ticket;
        } else {
            $tickets = Ticket::findAllByUserId($sessionId);
            foreach ($tickets as $ticket) {
                /**
                 * @var Ticket $ticket
                 */
                $lastReplyUserId = $ticket->getAttribute(Ticket::FIELD_LAST_REPLY_USER_ID);
                if ($lastReplyUserId == $sessionId) {
                    $ticket->setAttribute("reply_status", 0);
                } else {
                    $ticket->setAttribute("reply_status", 1);
                }
            }
            $data = $tickets;
        }

        return response([
            'data' => $data
        ]);
    }


    /**
     * save
     *
     * @param TicketSave $request
     * @return ResponseFactory|Response
     */
    public function save(TicketSave $request)
    {
        $sessionId = $request->session()->get('id');
        /**
         * @var User $user
         */
        $user = User::find($sessionId);
        if ($user == NULL) {
            abort(500, __('user.user.info.user_not_exist'));
        }

        $unprocessedTicketsCount = $user->countUnprocessedTickets();

        if ($unprocessedTicketsCount > 0) {
            abort(500, __('There are other unresolved tickets'));
        }

        DB::beginTransaction();
        $ticket = new Ticket();
        $ticket->setAttribute(Ticket::FIELD_SUBJECT, $request->get(Ticket::FIELD_SUBJECT));
        $ticket->setAttribute(Ticket::FIELD_LEVEL, $request->get(Ticket::FIELD_LEVEL));
        $ticket->setAttribute(Ticket::FIELD_USER_ID, $sessionId);
        $ticket->setAttribute(Ticket::FIELD_LAST_REPLY_USER_ID, $sessionId);

        if (!$ticket->save()) {
            DB::rollback();
            abort(500, __('Failed to open ticket'));
        }

        $ticketMessage = new TicketMessage();
        $ticketMessage->setAttribute(TicketMessage::FIELD_USER_ID, $sessionId);
        $ticketMessage->setAttribute(TicketMessage::FIELD_TICKET_ID, $ticket->getKey());
        $ticketMessage->setAttribute(TicketMessage::FIELD_MESSAGE, $request->get(TicketMessage::FIELD_MESSAGE));

        if (!$ticketMessage->save()) {
            DB::rollback();
            abort(500, __('Failed to open ticket'));
        }

        DB::commit();

        $this->_sendNotify($ticket, $ticketMessage);
        return response([
            'data' => true
        ]);
    }

    /**
     * reply
     *
     * @param Request $request
     * @return ResponseFactory|Response
     */
    public function reply(Request $request)
    {

        $reqId = (int)$request->input('id');
        $reqMessage = (string)$request->input('message');
        $sessionId = $request->session()->get('id');


        if ($reqId == 0) {
            abort(500, __('Invalid parameter'));
        }

        if (empty($reqMessage)) {
            abort(500, __('Message cannot be empty'));
        }

        $ticket = Ticket::findFirstByUserId($reqId, $sessionId);

        if ($ticket == null) {
            abort(500, __('Ticket does not exist'));
        }
        if ($ticket->getAttribute(Ticket::FIELD_STATUS) != Ticket::STATUS_OPEN) {
            abort(500, __('The ticket is closed and cannot be replied'));
        }

        $lastTicketMessage = $ticket->getLastMessage();

        if ($sessionId == $lastTicketMessage->getAttribute(TicketMessage::FIELD_USER_ID)) {
            abort(500, __('Please wait for the technical enginneer to reply'));
        }

        DB::beginTransaction();
        $ticketMessage = new TicketMessage();
        $ticketMessage->setAttribute(TicketMessage::FIELD_USER_ID, $sessionId);
        $ticketMessage->setAttribute(TicketMessage::FIELD_TICKET_ID, $ticket->getKey());
        $ticketMessage->setAttribute(TicketMessage::FIELD_MESSAGE, $reqMessage);
        $ticket->setAttribute(Ticket::FIELD_LAST_REPLY_USER_ID, $sessionId);

        if (!$ticketMessage->save() || !$ticket->save()) {
            DB::rollback();
            abort(500, __('Ticket reply failed'));
        }

        DB::commit();
        $this->_sendNotify($ticket, $ticketMessage);
        return response([
            'data' => true
        ]);
    }


    /**
     * close
     *
     * @param Request $request
     * @return ResponseFactory|Response
     */
    public function close(Request $request)
    {
        $reqId = (int)$request->input("id");
        $sessionId = $request->session()->get('id');

        if ($reqId <= 0) {
            abort(500, __('Invalid parameter'));
        }
        $ticket = Ticket::findFirstByUserId($reqId, $sessionId);

        if ($ticket == null) {
            abort(500, __('Ticket does not exist'));
        }

        $ticket->setAttribute(Ticket::FIELD_STATUS, Ticket::STATUS_CLOSE);
        if (!$ticket->save()) {
            abort(500, __('Close failed'));
        }

        return response([
            'data' => true
        ]);
    }


    public function withdraw(TicketWithdraw $request)
    {
        $reqWithdrawMethod = $request->input('withdraw_method');
        $reqWithdrawAccount = $request->input('withdraw_account');
        $sessionId = $request->session()->get('id');

        if ((int)config('v2board.withdraw_close_enable', 0)) {
            abort(500, __('Unsupported withdrawal'));
        }
        if (!in_array(
            $reqWithdrawMethod,
            config(
                'v2board.commission_withdraw_method',
                Dict::WITHDRAW_METHOD_WHITELIST_DEFAULT
            )
        )) {
            abort(500, __('Unsupported withdrawal method'));
        }
        /**
         * @var User $user
         */
        $user = User::find($sessionId);
        if ($user === null) {
            abort(500, __("The user does not exist"));
        }

        $limit = config('v2board.commission_withdraw_limit', 100);
        if ($limit > ($user->getAttribute(User::FIELD_COMMISSION_BALANCE) / 100)) {
            abort(500, __('The current required minimum withdrawal commission is :limit', ['limit' => $limit]));
        }
        DB::beginTransaction();
        $subject = __('[Commission Withdrawal Request] This ticket is opened by the system');

        $ticket = new Ticket();
        $ticket->setAttribute(Ticket::FIELD_SUBJECT, $subject);
        $ticket->setAttribute(Ticket::FIELD_LEVEL, Ticket::LEVEL_HIGH);
        $ticket->setAttribute(Ticket::FIELD_USER_ID, $sessionId);
        $ticket->setAttribute(Ticket::FIELD_LAST_REPLY_USER_ID, $sessionId);

        if (!$ticket->save()) {
            DB::rollback();
            abort(500, __('Failed to open ticket'));
        }
        $message = sprintf("%s\r\n%s",
            __('Withdrawal method') . "：" . $reqWithdrawMethod,
            __('Withdrawal account') . "：" .  $reqWithdrawAccount
        );

        $ticketMessage = new TicketMessage();
        $ticketMessage->setAttribute(TicketMessage::FIELD_USER_ID, $sessionId);
        $ticketMessage->setAttribute(TicketMessage::FIELD_TICKET_ID, $ticket->getKey());
        $ticketMessage->setAttribute(TicketMessage::FIELD_MESSAGE, $message);
        if (!$ticketMessage->save()) {
            DB::rollback();
            abort(500, __('Failed to open ticket'));
        }
        DB::commit();
        $this->_sendNotify($ticket, $ticketMessage);
        return response([
            'data' => true
        ]);
    }

    /**
     * send notify
     *
     * @param Ticket $ticket
     * @param TicketMessage $ticketMessage
     * @return void
     */
    private function _sendNotify(Ticket $ticket, TicketMessage $ticketMessage)
    {
        if (!config('v2board.telegram_bot_enable', 0)) {
            return;
        }
        $message = "📮工单提醒 #{$ticket->getKey()}\n———————————————\n主题：\n`{$ticket->getAttribute(Ticket::FIELD_SUBJECT)}`\n内容：\n`{$ticketMessage->getAttribute(TicketMessage::FIELD_MESSAGE)}`";
        TelegramService::sendMessageWithAdmin($message, true);
    }
}
