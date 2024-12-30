<?php

namespace NeoCube\Db\Adapter;

use PDO;

class Sqlite implements AdapterInterface {

    private static $instance;

    static function getConnection($db,$database) {
        if (!isset(self::$instance[$database])) {
            $dsn = 'sqlite:'.$db['dbname'];
            $PDO = new PDO($dsn);
            $PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$instance[$database] = $PDO;
        }
        return self::$instance[$database];
    }

}
