<?php

namespace NeoCube\Db\Adapter;

use PDO;

class Mysql implements AdapterInterface {

    private static array $instance = [];

    static function getConnection($db,$database) {
        if (!isset(self::$instance[$database])) {
            $dsn = 'mysql:host='.$db['host'].';dbname='.$db['dbname'];
            if ( isset($db['port']) ) $dsn .= ";port={$db['port']}";
            self::$instance[$database] = new PDO($dsn,$db['username'],$db['password'],isset($db['options'])?$db['options']:NULL);
        }
        return self::$instance[$database];
    }

}
