<?php

namespace App\Http\Controllers\Admin\Server;

use App\Models\Plan;
use App\Models\Server;
use App\Models\ServerGroup;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class GroupController extends Controller
{

    /**
     * fetch
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function fetch(Request $request)
    {
        $reqGroupId = (int)$request->input('group_id');
        if ($reqGroupId > 0) {
            $data = ServerGroup::find($reqGroupId);
        } else {
            $data = ServerGroup::get();
        }

        return response([
            'data' => $data
        ]);
    }

    /**
     * save
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function save(Request $request)
    {
        $reqId = (int)$request->input('id');
        $reqName = $request->input('name');
        if (empty($reqName)) {
            abort(500, '组名不能为空');
        }

        if ($reqId > 0) {
            $serverGroup = ServerGroup::find($reqId);
        } else {
            $serverGroup = new ServerGroup();
        }

        $serverGroup->setAttribute(ServerGroup::FIELD_NAME, $reqName);
        if (!$serverGroup->save()) {
            alert(500, "保存失败");
        }

        return response([
            'data' => true
        ]);
    }

    /**
     * drop
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function drop(Request $request)
    {
        $reqId = (int)$request->input('id');

        if ($reqId > 0) {
            $serverGroup = ServerGroup::find($reqId);
            if ($serverGroup == null) {
                abort(500, '组不存在');
            }
        }

        $servers = Server::all();
        foreach ($servers as $server) {
            if (in_array($reqId, $server->getAttribute(Server::FIELD_GROUP_ID))) {
                abort(500, '该组已被节点所使用，无法删除');
            }
        }

        if (Plan::where(Plan::FIELD_GROUP_ID, $reqId)->count() > 0) {
            abort(500, '该组已被订阅所使用，无法删除');
        }


        if (User::where(Plan::FIELD_GROUP_ID, $reqId)->count() > 0) {
            abort(500, '该组已被用户所使用，无法删除');
        }
        return response([
            'data' => $serverGroup->delete()
        ]);
    }
}
