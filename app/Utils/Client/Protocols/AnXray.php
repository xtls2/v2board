<?php

namespace App\Utils\Client\Protocols;

use App\Utils\Client\Protocol;

class AnXray extends Protocol
{
    public $flag = 'axxray';

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
            "encryption" => "none",
            "type" => urlencode($server['network']),
            "security" => $server['tls'] ? "tls" : "",
        ];

        if ($server['tls']) {
            if ($server['tls_settings']) {
                $tlsSettings = $server['tls_settings'];
                if (isset($tlsSettings['serverName']) && !empty($tlsSettings['serverName']))
                    $config['sni'] = urlencode($tlsSettings['serverName']);
            }
        }

        if ((string)$server['network'] === 'ws') {
            $wsSettings = $server['network_settings'];
            if (isset($wsSettings['path'])) {
                $config['path'] = urlencode($wsSettings['path']);
            }
            if (isset($wsSettings['headers']['Host'])) {
                $config['host'] = urlencode($wsSettings['headers']['Host']);
            }
        }
        if ((string)$server['network'] === 'grpc') {
            $grpcSettings = $server['network_settings'];
            if (isset($grpcSettings['serviceName']))  {
                $config['serviceName'] = urlencode($grpcSettings['serviceName']);
            }
        }
        return "vmess://" . $uuid . "@" . $server['host'] . ":" . $server['port'] . "?" . http_build_query($config) . "#" . urlencode($server['name']) . "\r\n";
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
