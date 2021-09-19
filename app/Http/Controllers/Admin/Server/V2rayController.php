<?php

namespace App\Http\Controllers\Admin\Server;

use App\Http\Requests\Admin\ServerV2raySave;
use App\Http\Requests\Admin\ServerV2rayUpdate;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Server;
use Illuminate\Http\Response;

class V2rayController extends Controller
{
    /**
     * save
     *
     * @param ServerV2raySave $request
     * @return Application|ResponseFactory|Response
     */
    public function save(ServerV2raySave $request)
    {
        $reqId = (int)$request->input('id');
        $reqName = $request->input('name');
        $reqGroupId = (array)$request->input('group_id');
        $reqParentId = $request->input('parent_id');
        $reqHost = $request->input('host');
        $reqPort = $request->input('port');
        $reqServerPort = $request->input('server_port');
        $reqTls = $request->input('tls');
        $reqTags = (array)$request->input('tags');
        $reqRate = $request->input('rate');
        $reqAlterId = $request->input('alter_id');
        $reqNetwork = $request->input('network');
        $reqNetworkSettings = $request->input('networkSettings');
        $reqRuleSettings = $request->input('ruleSettings');
        $reqTlsSettings = $request->input('tlsSettings');
        $reqDnsSettings = $request->input('dnsSettings');
        $reqShow = $request->input('show');

        /**
         * @var Server $server
         */
        if ($reqId > 0) {
            $server = Server::find($reqId);
            if ($server === null) {
                abort(500, '服务器不存在');
            }
        } else {
            $server = new Server();
        }

        $server->setAttribute(Server::FIELD_GROUP_ID, $reqGroupId);
        $server->setAttribute(Server::FIELD_NAME, $reqName);
        $server->setAttribute(Server::FIELD_NETWORK, $reqNetwork);
        $server->setAttribute(Server::FIELD_TLS, $reqTls);
        $server->setAttribute(Server::FIELD_RATE, $reqRate);
        $server->setAttribute(Server::FIELD_ALTER_ID, $reqAlterId);
        $server->setAttribute(Server::FIELD_HOST, $reqHost);
        $server->setAttribute(Server::FIELD_PORT, $reqPort);
        $server->setAttribute(Server::FIELD_SERVER_PORT, $reqServerPort);
        $server->setAttribute(Server::FIELD_PARENT_ID, $reqParentId);

        if ($reqShow) {
            $server->setAttribute(Server::FIELD_SHOW, $reqShow);
        }

        if ($reqTags) {
            $server->setAttribute(Server::FIELD_TAGS, $reqTags);
        }

        if ($reqDnsSettings) {
            $server->setAttribute(Server::FIELD_DNS_SETTINGS, $reqDnsSettings);
        }

        if ($reqRuleSettings) {
            $server->setAttribute(Server::FIELD_RULE_SETTINGS, $reqRuleSettings);
        }


        if ($reqNetworkSettings) {
            $server->setAttribute(Server::FIELD_NETWORK_SETTINGS, $reqNetworkSettings);
        }

        if ($reqTlsSettings) {
            $server->setAttribute(Server::FIELD_TLS_SETTINGS, $reqTlsSettings);
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
     */
    public function drop(Request $request)
    {
        $reqId = (int)$request->input('id');
        if ($reqId <= 0) {
            abort(500, "参数无效");
        }
        /**
         * @var Server $server
         */
        $server = Server::find($reqId);
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
     * update
     *
     * @param ServerV2rayUpdate $request
     * @return Application|ResponseFactory|Response
     */
    public function update(ServerV2rayUpdate $request)
    {
        $reqId = $request->input('id');
        $reqShow = $request->input('show');

        /**
         * @var Server $server
         */
        $server = Server::find($reqId);

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
     * @return Application|ResponseFactory|Response
     */
    public function copy(Request $request)
    {
        $reqInputId = $request->input('id');

        /**
         * @var Server $server
         */
        $server = Server::find($reqInputId);
        if ($server === null) {
            abort(500, '服务器不存在');
        }

        $newServer = $server->replicate();
        $newServer->setAttribute(Server::FIELD_SHOW, Server::SHOW_OFF);

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
         * @var Server $server
         */
        $server = Server::find($reqNodeId);
        if ($server === null) {
            abort(500, '节点不存在');
        }
        $configs = [];
        $configs['log_enable'] = config('v2board.server_log_enable');
        $configs['domain_rules'] = config('v2board.server_v2ray_domain');
        $configs['protocol_rules'] = config('v2board.server_v2ray_protocol');

        $json = $server->config(23333, $configs);


        return response([
            'data' => $json
        ]);
    }
}
