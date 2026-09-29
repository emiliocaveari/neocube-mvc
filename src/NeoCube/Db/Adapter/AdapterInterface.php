<?php

namespace NeoCube\Db\Adapter;

use PDO;
use Swoole\Database\PDOProxy;

interface AdapterInterface {

    static function getConnection($db,$database) : PDO|PDOProxy;

}
