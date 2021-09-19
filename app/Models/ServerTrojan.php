<?php

namespace App\Models;

use App\Utils\CacheKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use App\Models\Traits\Serialize;


class ServerTrojan extends Model
{
    use Serialize;
    const FIELD_ID = "id";
    const FIELD_GROUP_ID = "group_id";
    const FIELD_PARENT_ID = "parent_id";
    const FIELD_TAGS = "tags";
    const FIELD_NAME = "name";
    const FIELD_RATE = "rate";
    const FIELD_HOST = "host";
    const FIELD_PORT = "port";
    const FIELD_SERVER_PORT = "server_port";
    const FIELD_ALLOW_INSECURE = "allow_insecure";
    const FIELD_SERVER_NAME = "server_name";
    const FIELD_SHOW = "show";
    const FIELD_SORT = "sort";
    const FIELD_CREATED_AT = "created_at";
    const FIELD_UPDATED_AT = "updated_at";
    const CONFIG = '{"run_type":"server","local_addr":"0.0.0.0","local_port":443,"remote_addr":"www.taobao.com","remote_port":80,"password":[],"ssl":{"cert":"server.crt","key":"server.key","sni":"domain.com"},"api":{"enabled":true,"api_addr":"127.0.0.1","api_port":10000}}';
    const METHOD = 'trojan';

    const SHOW_ON = 1;
    const SHOW_OFF = 0;

    const TYPE = "trojan";

    protected $table = 'server_trojan';
    protected $dateFormat = 'U';


    protected $casts = [
        self::FIELD_CREATED_AT => 'timestamp',
        self::FIELD_UPDATED_AT => 'timestamp',
        self::FIELD_GROUP_ID => 'array',
        self::FIELD_TAGS => 'array'
    ];


    /**
     * check show
     *
     * @return bool
     */
    public function isShow()
    {
        return (bool)$this->getAttribute(self::FIELD_SHOW);
    }

    /**
     * find available users
     *
     * @return mixed
     */
    public function findAvailableUsers()
    {
        $server = new Server();
        $server->setAttribute(Server::FIELD_GROUP_ID, $this->getAttribute(Server::FIELD_GROUP_ID));
        return $server->findAvailableUsers();
    }

    /**
     * nodes
     * @return mixed
     */
    public static function nodes()
    {
        $servers = self::orderBy('sort', "ASC")->get();
        foreach ($servers as $server) {
            /**
             * @var ServerTrojan $server
             */
            $server->setAttribute("type", self::TYPE);
        }
        return $servers;

    }

    /**
     * config
     *
     * @param int $localPort
     * @return mixed
     */
    public function config(int $localPort)
    {
        $json = json_decode(self::CONFIG);
        $json->local_port = $this->getAttribute(self::FIELD_SERVER_PORT);
        $json->ssl->sni = $this->getAttribute(self::FIELD_SERVER_NAME) ?: $this->getAttribute(self::FIELD_HOST);
        $json->ssl->cert = "/root/.cert/server.crt";
        $json->ssl->key = "/root/.cert/server.key";
        $json->api->api_port = $localPort;
        return $json;
    }

    /**
     * configs
     *
     * @param User $user
     * @param bool $show
     * @return mixed
     */
    public static function configs(User $user, bool $show = true)
    {
        $servers = self::orderBy(self::FIELD_SORT, "ASC")->where(self::FIELD_SHOW, (int)$show)->get();
        foreach ($servers as $key => $server) {
            /**
             * @var ServerShadowsocks $server
             */
            $groupIds = $server->getAttribute(Server::FIELD_GROUP_ID);
            if (!in_array($user->getAttribute(User::FIELD_GROUP_ID), $groupIds)) {
                unset($servers[$key]);
                continue;
            }

            /**
             * @var ServerTrojan $server
             */
            $server->setAttribute("type", self::TYPE);
            if ($server->getAttribute(self::FIELD_PARENT_ID) > 0) {
                $server->setAttribute('last_check_at', Cache::get(CacheKey::get('SERVER_TROJAN_LAST_CHECK_AT',
                    $server->getAttribute(self::FIELD_PARENT_ID))));
            } else {
                $server->setAttribute('last_check_at', Cache::get(CacheKey::get('SERVER_TROJAN_LAST_CHECK_AT',
                    $server->getKey())));
            }
        }
        return $servers;
    }

}
