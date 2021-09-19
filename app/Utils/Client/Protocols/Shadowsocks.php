<?php

namespace App\Utils\Client\Protocols;

use App\Utils\Client\Protocol;
use RuntimeException;

class Shadowsocks extends Protocol
{
    public $flag = 'shadowsocks';

    public function handle()
    {
        $servers = $this->servers;
        $user = $this->user;

        $configs = [];
        $subs = [];
        $subs['servers'] = [];
        $subs['bytes_used'] = '';
        $subs['bytes_remaining'] = '';

        $bytesUsed = $user['u'] + $user['d'];
        $bytesRemaining = $user['transfer_enable'] - $bytesUsed;

        foreach ($servers as $item) {
            if ($item['type'] === 'shadowsocks') {
                array_push($configs, self::SIP008($item, $user));
            }
        }

        $subs['version'] = 1;
        $subs['bytes_used'] = $bytesUsed;
        $subs['bytes_remaining'] = $bytesRemaining;
        $subs['servers'] = array_merge($subs['servers'] ? $subs['servers'] : [], $configs);

        return json_encode($subs, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    public static function SIP008($server, $user)
    {
        $config = [
            "id" => $server['id'],
            "remarks" => $server['name'],
            "server" => $server['host'],
            "server_port" => $server['port'],
            "password" => $user['uuid'],
            "method" => $server['cipher']
        ];
        return $config;
    }


    public static function buildShadowsocks($password, $server)
    {
        throw new RuntimeException("The method is not implemented");
    }

    public static function buildTrojan($password, $server)
    {
        throw new RuntimeException("The method is not implemented");
    }

    public static function buildVmess($uuid, $server)
    {
        throw new RuntimeException("The method is not implemented");
    }

}
