<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Models\ServerShadowsocks;
use App\Models\ServerTrojan;
use Illuminate\Http\Request;
use App\Models\User;
use App\Utils\Client\Protocol;
use App\Services\ClientService;


class ClientController extends Controller
{
    /**
     * subscribe
     *
     * @param Request $request
     */
    public function subscribe(Request $request)
    {
        $reqFlag = $request->input('flag') ?? ($_SERVER['HTTP_USER_AGENT'] ?? '');
        $flag = strtolower($reqFlag);

        if (empty($flag)) {
            abort(500, "参数错误");
        }

        /**
         * @var User $user
         */
        $user = $request->user;
        // account not expired and is not banned.
        if (!$user->isAvailable()) {
            abort(500, "用户不可用");
        }


        $servers = array_merge(
            Server::configs($user)->toArray(),
            ServerShadowsocks::configs($user)->toArray(),
            ServerTrojan::configs($user)->toArray()
        );

        array_multisort(array_column($servers, 'sort'), SORT_ASC, $servers);

        if ($flag) {
            $protocolInstance = ClientService::getInstance($servers, $user, $flag);
            /**
             * @var Protocol $protocolInstance
             */

            if ($protocolInstance !== null) {
                die($protocolInstance->handle());
            }

            $protocolInstance = ClientService::getInstance($servers, $user, 'v2rayn');
            die($protocolInstance->handle());
        }

        die("目前只支持以下客户端, 请下载后使用软件订阅: ". join(",", ClientService::getProtoNames()));
    }
}
