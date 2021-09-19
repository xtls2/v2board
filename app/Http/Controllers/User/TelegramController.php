<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\TelegramService;

class TelegramController extends Controller
{
    public function getBotInfo()
    {
        $token =  config('v2board.telegram_bot_token');
        $telegramService = new TelegramService($token);
        $response = $telegramService->getMe();
        return response([
            'data' => [
                'username' => $response->result->username
            ]
        ]);
    }
}
