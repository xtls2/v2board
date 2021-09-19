<?php

namespace App\Services;

use App\Utils\Client\Factory;
use App\Utils\Client\Protocol;

class ClientService
{
    /**
     * 获取协议实例
     *
     * @param $servers
     * @param $user
     * @param $flag
     * @return Protocol|null
     */
    public static function getInstance($servers, $user, $flag): ?Protocol
    {
        return Factory::getInstance($servers, $user, $flag);
    }


    /**
     * 获取协议名称
     *
     * @return array
     */
    public static function getProtoNames(): array
    {
        return Factory::getProtocolNames();
    }
}
