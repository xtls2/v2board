<?php

namespace App\Http\Controllers\Admin\Server;

use App\Http\Requests\Admin\ServerShadowsocksSave;
use App\Http\Requests\Admin\ServerShadowsocksUpdate;
use App\Models\Server;
use App\Models\ServerShadowsocks;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ShadowsocksController extends Controller
{
    /**
     * save
     *
     * @param ServerShadowsocksSave $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function save(ServerShadowsocksSave $request)
    {
        $reqGroupId = (array)$request->input('group_id');
        $reqTags = (array)$request->input('tags');
        $reqId = (int)$request->input('id');
        $reqName = $request->input('name');
        $reqParentId = $request->input('parent_id');
        $reqHost = $request->input('host');
        $reqPort = $request->input('port');
        $reqServerPort = $request->input('server_port');
        $reqCipher = $request->input('cipher');
        $reqRate = $request->input('rate');
        $reqShow = $request->input('show');

        if ($reqId > 0) {
            /**
             * @var ServerShadowsocks $server
             */
            $server = ServerShadowsocks::find($reqId);
            if ($server === null) {
                abort(500, '服务器不存在');
            }

        } else {

            $server = new ServerShadowsocks();
        }

        $server->setAttribute(ServerShadowsocks::FIELD_NAME, $reqName);
        $server->setAttribute(ServerShadowsocks::FIELD_GROUP_ID, $reqGroupId);
        $server->setAttribute(ServerShadowsocks::FIELD_HOST, $reqHost);
        $server->setAttribute(ServerShadowsocks::FIELD_PORT, $reqPort);
        $server->setAttribute(ServerShadowsocks::FIELD_SERVER_PORT, $reqServerPort);
        $server->setAttribute(ServerShadowsocks::FIELD_CIPHER, $reqCipher);
        $server->setAttribute(ServerShadowsocks::FIELD_RATE, $reqRate);
        $server->setAttribute(ServerShadowsocks::FIELD_PARENT_ID, $reqParentId);

        if ($reqShow !== null) {
            $server->setAttribute(ServerShadowsocks::FIELD_SHOW, $reqShow);
        }

        if ($reqTags) {
            $server->setAttribute(ServerShadowsocks::FIELD_TAGS, $reqTags);
        }

        if (!$server->save()) {
            abort(500, '保存失败');
        }

        return response([
            'data' => true
        ]);
    }

    /**
     * drop
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function drop(Request $request)
    {
        $reqId = (int)$request->input('id');
        if ($reqId <= 0) {
            abort(500, "参数无效");
        }


        $server = ServerShadowsocks::find($reqId);
        if ($server === null) {
            abort(500, '节点ID不存在');
        }

        try {
            $server->delete();
        } catch (\Exception  $e) {
            abort(500, "删除失败" . $e->getMessage());
        }

        return response([
            'data' => true
        ]);
    }

    /**
     * update
     *
     * @param ServerShadowsocksUpdate $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function update(ServerShadowsocksUpdate $request)
    {
        $reqShow = $request->input('show');
        $reqId = $request->input('id');

        $server = ServerShadowsocks::find($reqId);

        /**
         * @var Server $server
         */
        if ($server === null) {
            abort(500, '该服务器不存在');
        }

        $server->setAttribute(Server::FIELD_SHOW, $reqShow);


        if (!$server->save()) {
            abort(500, '保存失败');
        }

        return response([
            'data' => true
        ]);
    }

    /**
     * copy
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function copy(Request $request)
    {
        $reqId = $request->input('id');

        /**
         * @var ServerShadowsocks $server
         */
        $server = ServerShadowsocks::find($reqId);
        if ($server === null) {
            abort(500, '服务器不存在');
        }

        $newServer = $server->replicate();

        $newServer->setAttribute(ServerShadowsocks::FIELD_SHOW, ServerShadowsocks::SHOW_OFF);

        if (!$newServer->save()) {
            abort(500, '复制失败');
        }

        return response([
            'data' => true
        ]);
    }
}
