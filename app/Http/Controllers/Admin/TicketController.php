<?php

namespace App\Http\Controllers\Admin;

use App\Services\MailService;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
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
        $reqId = (int)$request->input("id");
        $sessionId = $request->session()->get('id');

        if ($reqId > 0) {
            /**
             * @var Ticket $ticket
             */
            $ticket = Ticket::find($reqId);
            if ($ticket == null) {
                abort(500, '工单不存在');
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
            $data = [
                'data' => $ticket
            ];
        } else {
            $reqCurrent = $request->input('current') ?: 1;
            $reqPageSize = $request->input('pageSize') ?: 10;
            $reqStatus = $request->input('status');

            $models = Ticket::orderBy(Ticket::FIELD_CREATED_AT, "DESC");
            if ($reqStatus != null) {
                $models->where(Ticket::FIELD_STATUS, (int)$reqStatus);
            }
            $total = $models->count();
            $tickets = $models->forPage($reqCurrent, $reqPageSize)->get();
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

            $data = [
                'data' => $tickets,
                'total' => $total
            ];
        }
        return response($data);

    }

    /**
     * reply
     *
     * @param Request $request
     * @return ResponseFactory|Response
     */
    public function reply(Request $request)
    {
        $sessionId = $request->session()->get('id');
        $reqId = (int)$request->input('id');
        $reqMessage = (string)$request->input('message');

        if ($reqId <= 0) {
            abort(500, '参数错误');
        }

        if (empty($reqMessage)) {
            abort(500, '消息不能为空');
        }

        /**
         * @var Ticket $ticket
         */
        $ticket = Ticket::find($reqId);
        if ($ticket == null) {
            abort(500, '工单不存在');
        }

        if ($ticket->isClosed()) {
            abort(500, '工单已关闭，无法回复');
        }
        DB::beginTransaction();
        $ticketMessage = new TicketMessage();
        $ticketMessage->setAttribute(TicketMessage::FIELD_USER_ID, $sessionId);
        $ticketMessage->setAttribute(TicketMessage::FIELD_TICKET_ID, $reqId);
        $ticketMessage->setAttribute(TicketMessage::FIELD_MESSAGE, $reqMessage);
        $ticket->setAttribute(Ticket::FIELD_LAST_REPLY_USER_ID, $sessionId);

        if (!$ticketMessage->save() || !$ticket->save()) {
            DB::rollback();
            abort(500, '工单回复失败');
        }
        DB::commit();
        MailService::sendEmailNotify($ticket, $ticketMessage);

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
        $reqId = (int)$request->input('id');
        if ($reqId <= 0) {
            abort(500, '参数错误');
        }

        /**
         * @var Ticket $ticket
         */
        $ticket = Ticket::find($reqId);
        if ($ticket == null) {
            abort(500, '工单不存在');
        }

        $ticket->setAttribute(Ticket::FIELD_STATUS, Ticket::STATUS_CLOSE);

        if (!$ticket->save()) {
            abort(500, '关闭失败');
        }
        return response([
            'data' => true
        ]);
    }

}
