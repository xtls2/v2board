<?php

namespace App\Services;

use App\Jobs\SendTelegramJob;
use App\Models\User;
use \Curl\Curl;

class TelegramService
{
    protected $api;

    public function __construct(string  $token)
    {
        $this->api = 'https://api.telegram.org/bot' .  $token . '/';
    }

    public function sendMessage(int $chatId, string $text, string $parseMode = '')
    {
        $this->request('sendMessage', [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $parseMode
        ]);
    }

    public function getMe()
    {
        return $this->request('getMe');
    }

    public function setWebhook(string $url)
    {
        return $this->request('setWebhook', [
            'url' => $url
        ]);
    }

    private function request(string $method, array $params = [])
    {
        $curl = new Curl();

        if (empty($params)) {
            $url = $this->api . $method;
        } else {
            $url = $this->api . $method . '?' . http_build_query($params);
        }
        $curl->get($url);
        $response = $curl->response;
        if ($response == null) {
            abort(500,"TG接口未能返回数据");
        }

        $curl->close();
        if (!$response->ok) {
            abort(500, '来自TG的错误：' . $response->description);
        }
        return $response;
    }


    public static function sendMessageWithAdmin($message, $includeStaff = false)
    {
        $adminUsers = User::findAdminUsers($includeStaff);
        if (count($adminUsers) == 0) {
            return;
        }
        foreach ($adminUsers as $user) {
            SendTelegramJob::dispatch($user->telegram_id, $message);
        }
    }

}
