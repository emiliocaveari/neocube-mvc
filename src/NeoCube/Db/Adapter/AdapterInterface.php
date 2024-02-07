<?php

namespace NeoCube\Db\Adapter;

interface AdapterInterface {

    static function getConnection($db,$database);

}
