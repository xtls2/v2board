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
            abort(500, '参数有误，请携带订阅地址发送');
        }
        $subscribeUrl = $msg->args[0];
        $subscribeUrl = parse_url($subscribeUrl);
        parse_str($subscribeUrl['query'], $query);
        $token = $query['token'];
        if (!$token) {
            abort(500, '订阅地址无效');
        }

        /**
         * @var User $user
         */
        $user = User::findByToken($token);
        if ($user === null) {
            abort(500, '用户不存在');
        }

        if ($user->getAttribute(User::FIELD_TELEGRAM_ID)) {
            abort(500, '该账号已经绑定了Telegram账号');
        }

        $user->setAttribute(User::FIELD_TELEGRAM_ID, $msg->chat_id);
        if (!$user->save()) {
            abort(500, '设置失败');
        }
        $this->_service->sendMessage($msg->chat_id, '绑定成功');
    }

    private function _unbind()
    {
        $msg = $this->msg;
        if (!$msg->is_private) {
            return;
        }
        /**å
         * @var User $user
         */
        $user = User::findByTelegramId($msg->chat_id);
        if ($user === null) {
            $this->_help();
            $this->_service->sendMessage($msg->chat_id, '没有查询到您的用户信息，请先绑定账号', 'markdown');
            return;
        }
        $user->setAttribute(User::FIELD_TELEGRAM_ID, 0);
        if (!$user->save()) {
            abort(500, '解绑失败');
        }
        $this->_service->sendMessage($msg->chat_id, '解绑成功', 'markdown');
    }

    private function _help()
    {
        $msg = $this->msg;
        if (!$msg->is_private) return;
        $commands = [
            '/bind 订阅地址 - 绑定你的' . config('v2board.app_name', 'V2Board') . '账号',
            '/traffic - 查询流量信息',
            '/getlatesturl - 获取最新的' . config('v2board.app_name', 'V2Board') . '网址',
            '/unbind - 解除绑定'
        ];
        $text = implode(PHP_EOL, $commands);
        $this->_service->sendMessage($msg->chat_id, "你可以使用以下命令进行操作：\n\n$text", 'markdown');
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
            $this->_service->sendMessage($msg->chat_id, '没有查询到您的用户信息，请先绑定账号', 'markdown');
            return;
        }
        $transferEnable = Helper::trafficConvert($user->getAttribute(User::FIELD_TRANSFER_ENABLE));
        $up = Helper::trafficConvert($user->getAttribute(User::FIELD_U));
        $down = Helper::trafficConvert($user->getAttribute(User::FIELD_D));
        $remaining = Helper::trafficConvert($user->getAttribute(User::FIELD_TRANSFER_ENABLE) - ($user->getAttribute(User::FIELD_U) + $user->getAttribute(User::FIELD_D)));
        $text = "🚥流量查询\n———————————————\n计划流量：`$transferEnable`\n已用上行：`$up`\n已用下行：`$down`\n剩余流量：`$remaining`";
        $this->_service->sendMessage($msg->chat_id, $text, 'markdown');

    }

    private function _getLatestUrl()
    {
        $msg = $this->msg;
        $text = sprintf(
            "%s的最新网址是：%s",
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
            abort(500, '用户不存在');
        }

        if ($user->isAdmin() || $user->isStaff()) {
            /**
             * @var Ticket $ticket
             */
            $ticket = Ticket::find($ticketId);
            if ($ticket == null) {
                abort(500, '工单不存在');
            }

            if ($ticket->isClosed()) {
                abort(500, '工单已关闭，无法回复');
            }
            DB::beginTransaction();
            $ticketMessage = new TicketMessage();
            $ticketMessage->setAttribute(TicketMessage::FIELD_USER_ID, $user->getKey());
            $ticketMessage->setAttribute(TicketMessage::FIELD_TICKET_ID, $ticket->getKey());
            $ticketMessage->setAttribute(TicketMessage::FIELD_MESSAGE, $msg->text);
            $ticket->setAttribute(Ticket::FIELD_LAST_REPLY_USER_ID, $user->getKey());

            if (!$ticketMessage->save() || !$ticket->save()) {
                DB::rollback();
                abort(500, '工单回复失败');
            }
            DB::commit();
            MailService::sendEmailNotify($ticket, $ticketMessage);
        }

        if (!config('v2board.telegram_bot_enable', 0)) {
            return;
        }

        $this->_service->sendMessage($msg->chat_id, "#`$ticketId` 的工单已回复成功", 'markdown');
        TelegramService::sendMessageWithAdmin("#`{$ticketId}` 的工单已由 {$user->email} 进行回复", true);
    }

}
