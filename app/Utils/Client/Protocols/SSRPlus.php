<?php

namespace App\Utils\Client\Protocols;

use App\Utils\Client\Protocol;

class SSRPlus extends Protocol
{
    public $flag = 'ssrplus';

    public function handle(): string
    {
        $servers = $this->servers;
        $user = $this->user;
        $uri = '';

        foreach ($servers as $item) {
            if ($item['type'] === 'v2ray') {
                $uri .= self::buildVmess($user['uuid'], $item);
            }
            if ($item['type'] === 'shadowsocks') {
                $uri .= self::buildShadowsocks($user['uuid'], $item);
            }
            if ($item['type'] === 'trojan') {
                $uri .= self::buildTrojan($user['uuid'], $item);
            }
        }
        return base64_encode($uri);
    }

    public static function buildShadowsocks($password, $server): string
    {
        $name = rawurlencode($server['name']);
        $str = str_replace(
            ['+', '/', '='],
            ['-', '_', ''],
            base64_encode("{$server['cipher']}:$password")
        );
        return "ss://$str@{$server['host']}:{$server['port']}#$name\r\n";
    }

    public static function buildVmess($uuid, $server): string
    {
        $config = [
            "v" => "2",
            "ps" => $server['name'],
            "add" => $server['host'],
            "port" => (string)$server['port'],
            "id" => $uuid,
            "aid" => (string)$server['alter_id'],
            "net" => $server['network'],
            "type" => "none",
            "host" => "",
            "path" => "",
            "tls" => $server['tls'] ? "tls" : "",
        ];
        if ($server['tls']) {
            if ($server['tlsSettings']) {
                $tlsSettings = $server['tlsSettings'];
                if (isset($tlsSettings['serverName']) && !empty($tlsSettings['serverName'])) {
                    $config['sni'] = $tlsSettings['serverName'];
                }
            }
        }

        if ((string)$server['network'] === 'ws') {
            if (isset($server['network_settings'])) {
                $wsSettings = $server['network_settings'];
            }
            if (isset($wsSettings['path'])) {
                $config['path'] = $wsSettings['path'];
            }
            if (isset($wsSettings['headers']['Host'])) {
                $config['host'] = $wsSettings['headers']['Host'];
            }
        }
        if ((string)$server['network'] === 'grpc') {
            $grpcSettings = $server['network_settings'];
            if (isset($grpcSettings['path'])) {
                $config['path'] = $grpcSettings['serviceName'];
            }
        }
        return "vmess://" . base64_encode(json_encode($config)) . "\r\n";
    }

    public static function buildTrojan($password, $server): string
    {
        $name = rawurlencode($server['name']);
        $query = http_build_query([
            'allowInsecure' => $server['allow_insecure'],
            'peer' => $server['server_name'],
            'sni' => $server['server_name']
        ]);
        $uri = "trojan://$password@{$server['host']}:{$server['port']}?$query#$name";
        $uri .= "\r\n";
        return $uri;
    }

}
