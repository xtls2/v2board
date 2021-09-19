<?php

namespace App\Http\Controllers\Server;

use App\Http\Controllers\Controller;
use App\Jobs\ServerLogJob;
use App\Jobs\TrafficFetchJob;
use App\Models\Server;
use App\Models\ServerShadowsocks;
use App\Models\User;
use App\Utils\CacheKey;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/*
 * Tidal Lab Shadowsocks
 * Github: https://github.com/tokumeikoi/tidalab-ss
 */

class ShadowsocksTidalabController extends Controller
{
    /**
     * ShadowsocksTidalabController constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $reqToken = $request->input('token');
        if (empty($reqToken)) {
            abort(500, 'token is null');
        }
        if ($reqToken !== config('v2board.server_token')) {
            abort(500, 'token is error');
        }
    }

    /**
     * User
     *
     * @param Request $request
     * @return Application|ResponseFactory|Response
     */
    public function user(Request $request)
    {
        $reqNodeId = $request->input('node_id');
        /**
         * @var ServerShadowsocks $server
         */
        $server = ServerShadowsocks::find($reqNodeId);
        if ($server === null) {
            abort(500, 'fail');
        }

        Cache::put(CacheKey::get('SERVER_SHADOWSOCKS_LAST_CHECK_AT', $server->getKey()), time(), 3600);

        $result = [];
        if ($server->isShow()) {
            $users = $server->findAvailableUsers();
            foreach ($users as $user) {
                /**
                 * @var User $user
                 */
                array_push($result, [
                    'id' => $user->getKey(),
                    'port' => $server->getAttribute(ServerShadowsocks::FIELD_PORT),
                    'cipher' => $server->getAttribute(ServerShadowsocks::FIELD_CIPHER),
                    'secret' => $user->getAttribute(User::FIELD_UUID)
                ]);
            }
        }
        return response([
            'data' => $result
        ]);
    }

    /**
     * 后端提交数据
     *
     * @param Request $request
     * @return Application|ResponseFactory|Response
     */
    public function submit(Request $request)
    {
        $reqNodeId = $request->input('node_id');
        $server = ServerShadowsocks::find($reqNodeId);
        if ($server === null) {
            return response([
                'ret' => 0,
                'msg' => 'server is not found'
            ]);
        }
        $data = file_get_contents('php://input');

        $data = json_decode($data, true);
        if ($data === null || !is_array($data)) {
            return response([
                'ret' => 0,
                'msg' => 'params error'
            ]);
        }

        Cache::put(CacheKey::get('SERVER_SHADOWSOCKS_ONLINE_USER', $server->getKey()), count($data), 3600);
        Cache::put(CacheKey::get('SERVER_SHADOWSOCKS_LAST_PUSH_AT', $server->getKey()), time(), 3600);


        foreach ($data as $item) {
            $rate = $server->getAttribute(Server::FIELD_RATE);
            $u = $item[User::FIELD_U] * $rate;
            $d = $item[User::FIELD_D] * $rate;
            $userId = $item['user_id'];
            TrafficFetchJob::dispatch($u, $d, $userId);
            ServerLogJob::dispatch($u, $d, $userId, $server->getKey(), $rate, ServerShadowsocks::METHOD);
        }

        return response([
            'ret' => 1,
            'msg' => 'ok'
        ]);
    }
}
