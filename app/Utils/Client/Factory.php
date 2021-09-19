<?php


namespace App\Utils\Client;


class Factory
{

    /**
     * 获取protocol实例
     *
     * @param $servers
     * @param $user
     * @param $flag
     * @return Protocol|null
     */
    public static function getInstance($servers, $user, $flag): ?Protocol
    {
        $instance = null;
        $namespace = __NAMESPACE__ . "\\Protocols\\";
        $globPath = __DIR__ . DIRECTORY_SEPARATOR . "Protocols" . DIRECTORY_SEPARATOR . "*.php";
        foreach (glob($globPath) as $file) {
            $classFile = $namespace . basename($file, '.php');
            $classInstance = new $classFile($user, $servers, $flag);
            if (strpos($flag, $classInstance->flag) !== false) {
                $instance = $classInstance;
            }

        }
        return $instance;
    }

    /**
     * 获取协议名称集合
     *
     * @return array
     */
    public static function getProtocolNames(): array
    {
        $data = [];
        $globPath = __DIR__ . DIRECTORY_SEPARATOR . "Protocols" . DIRECTORY_SEPARATOR . "*.php";
        foreach (glob($globPath) as $file) {
            $classFile =  basename($file, '.php');
            array_push($data, $classFile);
        }
        return $data;
    }
}
