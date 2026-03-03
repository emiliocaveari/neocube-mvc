<?php

namespace NeoCube\Db\Adapter;

use PDO;

interface AdapterInterface {

    static function getConnection($db,$database) : PDO;

}
