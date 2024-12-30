<?php

namespace NeoCube\Db\Adapter;

use PDO;

class Postgre implements AdapterInterface {

    private static $instance;

    static function getConnection($db,$database) {
        if (!isset(self::$instance[$database])) {
            $PDO = new PDO("pgsql:dbname={$db['dbname']} host={$db['host']}",$db['username'],$db['password']);
            $PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$instance[$database] = $PDO;
        }
        return self::$instance[$database];
    }

}
