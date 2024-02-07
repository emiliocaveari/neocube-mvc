<?php

namespace NeoCube\Db\Adapter;

class Sqlite implements AdapterInterface {

    private static $instance;

    static function getConnection($db,$database) {
        if (!isset(self::$instance[$database])) {
            $dsn = 'sqlite:'.$db['dbname'];
            self::$instance[$database] = new \PDO($dsn);
        }
        return self::$instance[$database];
    }

}
