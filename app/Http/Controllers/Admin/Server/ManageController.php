<?php

namespace App\Http\Controllers\Admin\Server;

use App\Models\Server;
use App\Models\ServerShadowsocks;
use App\Models\ServerTrojan;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Utils\CacheKey;


class ManageController extends Controller
{
    /**
     * get nodes
     *
     * @param Request $request
     * @return Application|ResponseFactory|Response
     */
    public function getNodes(Request $request)
    {
        //get shadowSocks servers
        $shadowServers = ServerShadowsocks::nodes();
        $v2rayServers = Server::nodes();
        $trojanServers = ServerTrojan::nodes();

        $servers = array_merge(
            $shadowServers->toArray(),
            $v2rayServers->toArray(),
            $trojanServers->toArray()
        );

        foreach ($servers as &$server) {
            $serverType = strtoupper($server['type']);
            $cacheKeyOnline = CacheKey::get("SERVER_{$serverType}_ONLINE_USER", $server['parent_id'] ?: $server['id']);
            $server['online'] = Cache::get($cacheKeyOnline) ?? 0;
            if ($server['parent_id']) {
                $server['last_check_at'] = Cache::get(CacheKey::get("SERVER_{$serverType}_LAST_CHECK_AT", $server['parent_id']));
                $server['last_push_at'] = Cache::get(CacheKey::get("SERVER_{$serverType}_LAST_PUSH_AT", $server['parent_id']));
            } else {
                $server['last_check_at'] = Cache::get(CacheKey::get("SERVER_{$serverType}_LAST_CHECK_AT", $server['id']));
                $server['last_push_at'] = Cache::get(CacheKey::get("SERVER_{$serverType}_LAST_PUSH_AT", $server['id']));
            }

            if ((time() - 300) >= $server['last_check_at']) {
                $server['available_status'] = 0;
            } else if ((time() - 300) >= $server['last_push_at']) {
                $server['available_status'] = 1;
            } else {
                $server['available_status'] = 2;
            }
        }

        array_multisort(array_column($servers, 'sort'), SORT_ASC, $servers);
        return response([
            'data' => $servers
        ]);
    }

    /**
     * sort
     *
     * @param Request $request
     * @return Application|ResponseFactory|Response
     */
    public function sort(Request $request)
    {
        $reqSorts = $request->input('sorts');
        DB::beginTransaction();
        foreach ($reqSorts as $index => $item) {
            $method = $item['key'];
            $serverID = $item['value'];
            /**
             * @var Server $server
             */
            $server = null;
            switch ($method) {
                case ServerShadowsocks::TYPE:
                    $server = ServerShadowsocks::find($serverID);
                    break;
                case Server::TYPE:
                    $server = Server::find($serverID);
                    break;
                case ServerTrojan::TYPE:
                    $server = ServerTrojan::find($serverID);
                    break;
            }

            if ($server === null) {
                DB::rollBack();
                abort(500, '服务器未找到');
            }
            $server->setAttribute(Server::FIELD_SORT, $index);
            if (!$server->save()) {
                DB::rollBack();
                abort(500, '保存失败');
            }
        }
        DB::commit();
        return response([
            'data' => true
        ]);
    }

}
