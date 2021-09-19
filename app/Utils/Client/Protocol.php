<?php

namespace App\Utils\Client;

abstract class Protocol
{
    public $flag;
    protected $servers;
    protected $user;
    protected $requestFlag;

    public function __construct($user, $servers, $requestFlag)
    {
        $this->user = $user;
        $this->servers = $servers;
        $this->requestFlag = $requestFlag;
    }

    abstract public function handle();

    abstract public static  function buildShadowsocks($password, $server);

    abstract public static  function buildTrojan($password, $server);

    abstract public static function buildVmess($uuid, $server);

}
