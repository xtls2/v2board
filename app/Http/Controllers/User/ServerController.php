<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ServerShadowsocks;
use App\Models\ServerTrojan;
use Illuminate\Http\Request;
use App\Models\Server;
use App\Models\ServerLog;
use App\Models\User;
use Illuminate\Http\Response;

class ServerController extends Controller
{
    public function fetch(Request $request)
    {
        $sessionId = $request->session()->get('id');
        /**
         * @var User $user
         */
        $user = User::find($sessionId);
        if ($user === null) {
            abort(500, __('The user does not exist'));
        }

        $servers = [];
        if ($user->isAvailable()) {
            $shadowServers = ServerShadowsocks::configs($user);
            $v2rayServers = Server::configs($user);
            $trojanServers = ServerTrojan::configs($user);

            $servers = array_merge(
                $shadowServers->toArray(),
                $v2rayServers->toArray(),
                $trojanServers->toArray()
            );
            array_multisort(array_column($servers, 'sort'), SORT_ASC, $servers);
        }
        return response([
            'data' => $servers
        ]);
    }


    /**
     *  fetch log
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|Response
     */
    public function fetchLog(Request $request)
    {
        $reqType = (int)$request->input('type') ? $request->input('type') : 0;
        $reqCurrent = (int)$request->input('current') ? $request->input('current') : 1;
        $reqPageSize = (int)$request->input('pageSize') >= 10 ? $request->input('pageSize') : 10;
        $sessionId = $request->session()->get('id');

        $serverLogModel = ServerLog::where(ServerLog::FIELD_USER_ID, $sessionId)
            ->orderBy(ServerLog::FIELD_LOG_AT, "DESC");
        switch ($reqType) {
            case 0:
                $serverLogModel->where(ServerLog::FIELD_LOG_AT, '>=', strtotime(date('Y-m-d')));
                break;
            case 1:
                $serverLogModel->where(ServerLog::FIELD_LOG_AT, '>=', strtotime(date('Y-m-d')) - 604800);
                break;
            case 2:
                $serverLogModel->where(ServerLog::FIELD_LOG_AT, '>=', strtotime(date('Y-m-1')));
        }
        $total = $serverLogModel->count();
        $res = $serverLogModel->forPage($reqCurrent, $reqPageSize)->get();

        return response([
            'data' => $res,
            'total' => $total
        ]);
    }
}
