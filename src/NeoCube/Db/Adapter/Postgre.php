<?php

namespace NeoCube\Db\Adapter;

class Postgre implements AdapterInterface {

    private static $instance;

    static function getConnection($db,$database) {
        if (!isset(self::$instance[$database])) {
            self::$instance[$database] = new \PDO("pgsql:dbname={$db['dbname']} host={$db['host']}",$db['username'],$db['password']);
        }
        return self::$instance[$database];
    }

}
