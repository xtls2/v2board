<?php

namespace App\Http\Controllers\Server;

use App\Http\Controllers\Controller;
use App\Jobs\ServerLogJob;
use App\Jobs\TrafficFetchJob;
use App\Models\Server;
use App\Models\User;
use App\Utils\CacheKey;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/*
 * V2ray Aurora
 * Github: https://github.com/tokumeikoi/aurora
 */

class DeepbworkController extends Controller
{
    /**
     * DeepbworkController constructor.
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
     * 后端获取用户User
     *
     * @param Request $request
     * @return Application|ResponseFactory|Response
     */
    public function user(Request $request)
    {
        $reqNodeId = $request->input('node_id');
        /**
         * @var Server $server
         */
        $server = Server::find($reqNodeId);
        if ($server === null) {
            return response([
                'msg' => 'false',
                'data' => 'server is not found',
            ]);
        }

        Cache::put(CacheKey::get('SERVER_V2RAY_LAST_CHECK_AT', $server->getKey()), time(), 3600);
        $result = [];
        if ($server->isShow()) {
            $users = $server->findAvailableUsers();
            foreach ($users as $user) {
                /**
                 * @var User $user
                 */
                $user->setAttribute("v2ray_user", [
                    "uuid" => $user->getAttribute(User::FIELD_UUID),
                    "email" => sprintf("%s@v2board.user", $user->getAttribute(User::FIELD_UUID)),
                    "alter_id" => $server->getAttribute(Server::FIELD_ALTER_ID),
                    "level" => 0,
                ]);
                unset($user['uuid']);
                unset($user['email']);
                array_push($result, $user);
            }
        }


        return response([
            'msg' => 'ok',
            'data' => $result,
        ]);
    }

    /**
     * submit
     * @param Request $request
     * @return Application|ResponseFactory|Response
     */
    public function submit(Request $request)
    {
        $reqNodeId = $request->input('node_id');

        /**
         * @var Server $server
         */
        $server = Server::find($reqNodeId);
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

        Cache::put(CacheKey::get('SERVER_V2RAY_ONLINE_USER', $server->getKey()), count($data), 3600);
        Cache::put(CacheKey::get('SERVER_V2RAY_LAST_PUSH_AT', $server->getKey()), time(), 3600);
        foreach ($data as $item) {
            $rate = $server->getAttribute(Server::FIELD_RATE);
            $u = $item[User::FIELD_U] * $rate;
            $d = $item[User::FIELD_D] * $rate;
            $userId = $item['user_id'];
            TrafficFetchJob::dispatch($u, $d, $userId);
            ServerLogJob::dispatch($u, $d, $userId, $server->getKey(), $rate, Server::METHOD);
        }

        return response([
            'ret' => 1,
            'msg' => 'ok'
        ]);
    }

    /**
     * config
     *
     * @param Request $request
     */
    public function config(Request $request)
    {
        $reqNodeId = $request->input('node_id');
        $reqLocalPort = $request->input('local_port');
        if (empty($reqNodeId) || empty($reqLocalPort)) {
            abort(500, '参数错误');
        }
        /**
         * @var Server $server
         */
        $server = Server::find($reqNodeId);
        if ($server === null) {
            abort(500, 'server is not found');
        }

        try {
            $configs = [];
            $configs['log_enable'] = config('v2board.server_log_enable');
            $configs['domain_rules'] = config('v2board.server_v2ray_domain');
            $configs['protocol_rules'] = config('v2board.server_v2ray_protocol');

            $json = $server->config($reqLocalPort, $configs);
            die(json_encode($json, JSON_UNESCAPED_UNICODE));

        } catch (Exception $e) {
            abort(500, $e->getMessage());
        }

    }
}
