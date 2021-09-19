<?php

namespace App\Http\Controllers\Admin\Server;

use App\Http\Requests\Admin\ServerTrojanSave;
use App\Http\Requests\Admin\ServerTrojanUpdate;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ServerTrojan;
use Illuminate\Http\Response;

class TrojanController extends Controller
{

    /**
     * save
     *
     * @param ServerTrojanSave $request
     * @return Application|ResponseFactory|Response
     */
    public function save(ServerTrojanSave $request)
    {
        $reqId = (int)$request->input('id');
        $reqName = $request->input('name');
        $reqGroupId = (array)$request->input('group_id');
        $reqParentId = $request->input('parent_id');
        $reqHost = $request->input('host');
        $reqPort = $request->input('port');
        $reqServerPort = $request->input('server_port');
        $reqAllowInsecure = $request->input('allow_insecure');
        $reqServerName = $request->input('server_name');
        $reqTags = (array)$request->input('tags');
        $reqRate = $request->input('rate');
        $reqShow = $request->input('show');

        if ($reqId > 0) {
            /**
             * @var  ServerTrojan $server
             */
            $server = ServerTrojan::find($reqId);
            if ($server === null) {
                abort(500, '服务器不存在');
            }
        } else {
            $server = new ServerTrojan();
        }

        $server->setAttribute(ServerTrojan::FIELD_NAME, $reqName);
        $server->setAttribute(ServerTrojan::FIELD_GROUP_ID, $reqGroupId);
        $server->setAttribute(ServerTrojan::FIELD_HOST, $reqHost);
        $server->setAttribute(ServerTrojan::FIELD_PORT, $reqPort);
        $server->setAttribute(ServerTrojan::FIELD_SERVER_PORT, $reqServerPort);
        $server->setAttribute(ServerTrojan::FIELD_RATE, $reqRate);
        $server->setAttribute(ServerTrojan::FIELD_PARENT_ID, $reqParentId);


        if ($reqAllowInsecure !== null) {
            $server->setAttribute(ServerTrojan::FIELD_ALLOW_INSECURE, $reqAllowInsecure);
        }

        if ($reqServerName !== null) {
            $server->setAttribute(ServerTrojan::FIELD_SERVER_NAME, $reqServerName);
        }

        if ($reqTags) {
            $server->setAttribute(ServerTrojan::FIELD_TAGS, $reqTags);
        }

        if ($reqShow !== null) {
            $server->setAttribute(ServerTrojan::FIELD_SHOW, $reqShow);
        }


        if (!$server->save()) {
            abort(500, '创建失败');
        }

        return response([
            'data' => true
        ]);
    }

    /**
     * drop
     *
     * @param Request $request
     * @return Application|ResponseFactory|Response
     * @throws Exception
     */
    public function drop(Request $request)
    {
        $reqId = (int)$request->input('id');
        if ($reqId <= 0) {
            abort(500, "参数无效");
        }
        /**
         * @var ServerTrojan $server
         */
        $server = ServerTrojan::find($reqId);
        if ($server === null) {
            abort(500, '节点ID不存在');
        }

        try {
            $server->delete();
        } catch (Exception  $e) {
            abort(500, "删除失败" . $e->getMessage());
        }

        return response([
            'data' => true
        ]);
    }

    /**
     * torjan
     *
     * @param ServerTrojanUpdate $request
     * @return Application|ResponseFactory|Response
     */
    public function update(ServerTrojanUpdate $request)
    {
        $reqId = $request->input('id');
        $reqShow = $request->input('show');

        /**
         * @var ServerTrojan $server
         */
        $server = ServerTrojan::find($reqId);

        if ($server === null) {
            abort(500, '该服务器不存在');
        }

        $server->setAttribute(ServerTrojan::FIELD_SHOW, $reqShow);

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
     * @return Application|ResponseFactory|Response
     */
    public function copy(Request $request)
    {
        $reqId = $request->input('id');

        /**
         * @var ServerTrojan $server
         */
        $server = ServerTrojan::find($reqId);
        if ($server === null) {
            abort(500, '服务器不存在');
        }


        $newServer = $server->replicate();
        $newServer->setAttribute(ServerTrojan::FIELD_SHOW, ServerTrojan::SHOW_OFF);

        if (!$newServer->save()) {
            abort(500, '复制失败');
        }

        return response([
            'data' => true
        ]);
    }

    /**
     * view config
     *
     * @param Request $request
     * @return Application|ResponseFactory|Response
     */
    public function viewConfig(Request $request)
    {
        $reqNodeId = $request->input('node_id');

        /**
         * @var ServerTrojan $server
         */
        $server = ServerTrojan::find($reqNodeId);
        if ($server === null) {
            abort(500, '节点不存在');
        }

        return response([
            'data' => $server->config(23333)
        ]);
    }
}
