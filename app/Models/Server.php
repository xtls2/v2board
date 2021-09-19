<?php

namespace App\Models;

use App\Models\Traits\Serialize;
use App\Utils\CacheKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use StdClass;

class Server extends Model
{
    use Serialize;
    const CONFIG = '{"api":{"services":["HandlerService","StatsService"],"tag":"api"},"dns":{},"stats":{},"inbound":{"port":443,"protocol":"vmess","settings":{"clients":[]},"sniffing":{"enabled":true,"destOverride":["http","tls"]},"streamSettings":{"network":"tcp"},"tag":"proxy"},"inboundDetour":[{"listen":"127.0.0.1","port":23333,"protocol":"dokodemo-door","settings":{"address":"0.0.0.0"},"tag":"api"}],"log":{"loglevel":"debug","access":"access.log","error":"error.log"},"outbound":{"protocol":"freedom","settings":{}},"outboundDetour":[{"protocol":"blackhole","settings":{},"tag":"block"}],"routing":{"rules":[{"inboundTag":"api","outboundTag":"api","type":"field"}]},"policy":{"levels":{"0":{"handshake":4,"connIdle":300,"uplinkOnly":5,"downlinkOnly":30,"statsUserUplink":true,"statsUserDownlink":true}}}}';

    const FIELD_ID = "id";
    const FIELD_GROUP_ID = "group_id";
    const FIELD_NAME = "name";
    const FIELD_PARENT_ID = "parent_id";
    const FIELD_HOST = "host";
    const FIELD_PORT = "port";
    const FIELD_SERVER_PORT = "server_port";
    const FIELD_TLS = "tls";
    const FIELD_TAGS = "tags";
    const FIELD_RATE = "rate";
    const FIELD_NETWORK = "network";
    const FIELD_ALTER_ID = "alter_id"; //变更ID
    const FIELD_SETTINGS = "settings";
    const FIELD_RULES = "rules";
    const FIELD_NETWORK_SETTINGS = "network_settings";
    const FIELD_TLS_SETTINGS = "tls_settings";
    const FIELD_RULE_SETTINGS = "rule_settings";
    const FIELD_DNS_SETTINGS = "dns_settings";
    const FIELD_SHOW = "show";
    const FIELD_SORT = "sort";
    const FIELD_CREATED_AT = "created_at";
    const FIELD_UPDATED_AT = "updated_at";

    const SHOW_ON = 1;
    const SHOW_OFF = 0;

    const TYPE = "v2ray";
    const METHOD = "vmess";
    protected $table = 'server';
    protected $dateFormat = 'U';


    protected $casts = [
        self::FIELD_CREATED_AT => 'timestamp',
        self::FIELD_UPDATED_AT => 'timestamp',
        self::FIELD_GROUP_ID => 'array',
        self::FIELD_TLS_SETTINGS => 'array',
        self::FIELD_NETWORK_SETTINGS => 'array',
        self::FIELD_DNS_SETTINGS => 'array',
        self::FIELD_RULE_SETTINGS=> 'array',
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
        $groupIds = $this->getAttribute(self::FIELD_GROUP_ID);
        if (empty($groupIds)) {
            return [];
        }
        return User::whereIn(User::FIELD_GROUP_ID, $groupIds)->where(User::FIELD_BANNED, User::BANNED_OFF)
            ->whereRaw('u + d < transfer_enable')->where(function ($query) {
                $query->where(User::FIELD_EXPIRED_AT, '>=', time())
                    ->orWhere(User::FIELD_EXPIRED_AT, NULL)->orWhere(User::FIELD_EXPIRED_AT, 0);
            })->select([User::FIELD_ID, User::FIELD_EMAIL, User::FIELD_T, User::FIELD_U, User::FIELD_D,
                User::FIELD_TRANSFER_ENABLE, User::FIELD_UUID])->get();
    }


    /**
     * v2ray config
     *
     * @param int $localPort
     * @param array $configs
     * @return mixed
     */
    public function config(int $localPort, array $configs)
    {
        $json = json_decode(self::CONFIG);
        $json->log->loglevel = $configs['log_enable'] ? 'debug' : 'none';
        $json->inboundDetour[0]->port = $localPort;
        $json->inbound->port = (int)$this->getAttribute(self::FIELD_SERVER_PORT);
        $json->inbound->streamSettings->network = $this->getAttribute(self::FIELD_NETWORK);

        if ($this->getAttribute(self::FIELD_DNS_SETTINGS)) {
            $dns = $this->getAttribute(self::FIELD_DNS_SETTINGS);
            if (isset($dns->servers)) {
                array_push($dns->servers, '1.1.1.1');
                array_push($dns->servers, 'localhost');
            }
            $json->dns = $dns;
            $json->outbound->settings->domainStrategy = 'UseIP';
        }


        if ($this->getAttribute(self::FIELD_NETWORK_SETTINGS)) {
            $networkSettings = $this->getAttribute(self::FIELD_NETWORK_SETTINGS);
            switch ($this->getAttribute(self::FIELD_NETWORK)) {
                case 'tcp':
                    $json->inbound->streamSettings->tcpSettings = $networkSettings;
                    break;
                case 'kcp':
                    $json->inbound->streamSettings->kcpSettings = $networkSettings;
                case 'ws':
                    $json->inbound->streamSettings->wsSettings = $networkSettings;
                    break;
                case 'http':
                    $json->inbound->streamSettings->httpSettings = $networkSettings;
                    break;
                case 'domainsocket':
                    $json->inbound->streamSettings->dsSettings = $networkSettings;
                    break;
                case 'quic':
                    $json->inbound->streamSettings->quicSettings = $networkSettings;
                    break;
                case 'grpc':
                    $json->inbound->streamSettings->grpcSettings = $networkSettings;
                    break;
            }
        }

        $domainRules = array_filter(explode(PHP_EOL, $configs['domain_rules']));
        $protocolRules = array_filter(explode(PHP_EOL, $configs['protocol_rules']));

        if ($this->getAttribute(self::FIELD_RULE_SETTINGS)) {
            $ruleSettings = $this->getAttribute(self::FIELD_RULE_SETTINGS);
        }
        // domain
        if (isset($ruleSettings->domain)) {
            $ruleSettings->domain = array_filter($ruleSettings->domain);
            if (!empty($ruleSettings->domain)) {
                $domainRules = array_merge($domainRules, $ruleSettings->domain);
            }
        }
        // protocol
        if (isset($ruleSettings->protocol)) {
            $ruleSettings->protocol = array_filter($ruleSettings->protocol);
            if (!empty($ruleSettings->protocol)) {
                $protocolRules = array_merge($protocolRules, $ruleSettings->protocol);
            }
        }

        if (!empty($domainRules)) {
            $domainObj = new StdClass();
            $domainObj->type = 'field';
            $domainObj->domain = $domainRules;
            $domainObj->outboundTag = 'block';
            array_push($json->routing->rules, $domainObj);
        }

        if (!empty($protocolRules)) {
            $protocolObj = new StdClass();
            $protocolObj->type = 'field';
            $protocolObj->protocol = $protocolRules;
            $protocolObj->outboundTag = 'block';
            array_push($json->routing->rules, $protocolObj);
        }
        if (empty($domainRules) && empty($protocolRules)) {
            $json->inbound->sniffing->enabled = false;
        }


        if ((int)$this->getAttribute(self::FIELD_TLS)) {
            $tlsSettings = $this->getAttribute(self::FIELD_TLS_SETTINGS);
            $json->inbound->streamSettings->security = 'tls';
            $tls = (object)[
                'certificateFile' => '/root/.cert/server.crt',
                'keyFile' => '/root/.cert/server.key'
            ];
            $json->inbound->streamSettings->tlsSettings = new StdClass();
            if (isset($tlsSettings->serverName)) {
                $json->inbound->streamSettings->tlsSettings->serverName = (string)$tlsSettings->serverName;
            }
            if (isset($tlsSettings->allowInsecure)) {
                $json->inbound->streamSettings->tlsSettings->allowInsecure = (bool)((int)$tlsSettings->allowInsecure);
            }
            $json->inbound->streamSettings->tlsSettings->certificates[0] = $tls;
        }
        return $json;
    }

    /**
     * nodes
     *
     * @return mixed
     */
    public static function nodes()
    {
        $servers = self::orderBy('sort', "ASC")->get();
        foreach ($servers as $server) {
            /**
             * @var Server $server
             */
            $server->setAttribute("type", self::TYPE);
        }

        return $servers;
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
             * @var Server $server
             */
            $groupIds = $server->getAttribute(Server::FIELD_GROUP_ID);
            if (!in_array($user->getAttribute(User::FIELD_GROUP_ID), $groupIds)) {
                unset($servers[$key]);
                continue;
            }

            $server->setAttribute("type", self::TYPE);
            if ($server->getAttribute(self::FIELD_PARENT_ID) > 0) {
                $server->setAttribute('last_check_at', Cache::get(CacheKey::get('SERVER_V2RAY_LAST_CHECK_AT',
                    $server->getAttribute(self::FIELD_PARENT_ID))));
            } else {
                $server->setAttribute('last_check_at', Cache::get(CacheKey::get('SERVER_V2RAY_LAST_CHECK_AT',
                    $server->getKey())));
            }
        }
        return $servers;
    }
}


