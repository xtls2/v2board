<?php

namespace App\Utils\Client\Protocols;

use App\Utils\Client\Protocol;

class QuantumultX extends Protocol
{
    public $flag = 'quantumult%20x';


    public function handle()
    {
        $servers = $this->servers;
        $user = $this->user;
        $uri = '';
        header("subscription-userinfo: upload={$user['u']}; download={$user['d']}; total={$user['transfer_enable']}; expire={$user['expired_at']}");
        foreach ($servers as $item) {
            if ($item['type'] === 'shadowsocks') {
                $uri .= self::buildShadowsocks($user['uuid'], $item);
            }
            if ($item['type'] === 'v2ray') {
                $uri .= self::buildVmess($user['uuid'], $item);
            }
            if ($item['type'] === 'trojan') {
                $uri .= self::buildTrojan($user['uuid'], $item);
            }
        }
        return base64_encode($uri);
    }

    public static function buildShadowsocks($password, $server): string
    {
        $config = [
            "shadowsocks={$server['host']}:{$server['port']}",
            "method={$server['cipher']}",
            "password=$password",
            'fast-open=true',
            'udp-relay=true',
            "tag={$server['name']}"
        ];
        $config = array_filter($config);
        $uri = implode(',', $config);
        $uri .= "\r\n";
        return $uri;
    }

    public static function buildVmess($uuid, $server): string
    {
        $config = [
            "vmess={$server['host']}:{$server['port']}",
            'method=chacha20-poly1305',
            "password=$uuid",
            'fast-open=true',
            'udp-relay=true',
            "tag={$server['name']}"
        ];

        if ($server['tls']) {
            if ($server['network'] === 'tcp')
                array_push($config, 'obfs=over-tls');
            if ($server['tls_settings']) {
                $tlsSettings = $server['tls_settings'];
                if (isset($tlsSettings['allowInsecure']) && !empty($tlsSettings['allowInsecure'])) {
                    array_push($config, 'tls-verification=' . ($tlsSettings['allowInsecure'] ? 'false' : 'true'));
                }
                if (isset($tlsSettings['serverName']) && !empty($tlsSettings['serverName'])) {
                    $host = $tlsSettings['serverName'];
                }
            }
        }
        if ($server['network'] === 'ws') {
            if ($server['tls'])
                array_push($config, 'obfs=wss');
            else
                array_push($config, 'obfs=ws');
            if (isset($server['network_settings'])) {
                $wsSettings = $server['network_settings'];
                if (isset($wsSettings['path']) && !empty($wsSettings['path'])) {
                    array_push($config, "obfs-uri={$wsSettings['path']}");
                }
                if (isset($wsSettings['headers']['Host']) && !empty($wsSettings['headers']['Host']) && !isset($host)) {
                    $host = $wsSettings['headers']['Host'];
                }
            }
        }
        if (isset($host)) {
            array_push($config, "obfs-host=$host");
        }

        $uri = implode(',', $config);
        $uri .= "\r\n";
        return $uri;
    }

    public static function buildTrojan($password, $server): string
    {
        $config = [
            "trojan={$server['host']}:{$server['port']}",
            "password=$password",
            'over-tls=true',
            $server['server_name'] ? "tls-host={$server['server_name']}" : "",
            // Tips: allowInsecure=false = tls-verification=true
            $server['allow_insecure'] ? 'tls-verification=false' : 'tls-verification=true',
            'fast-open=true',
            'udp-relay=true',
            "tag={$server['name']}"
        ];
        $config = array_filter($config);
        $uri = implode(',', $config);
        $uri .= "\r\n";
        return $uri;
    }
}
