<?php

namespace NeoCube\Db;

use NeoCube\Application;
use NeoCube\Error\ErrorType;
use NeoCube\Db\Adapter\Mysql;
use NeoCube\Db\Adapter\Postgre;
use NeoCube\Db\Adapter\Sqlite;

class Connection {

    private static $connections = null;

    public static function factory(string $database = 'database_main') {
        try {
            if ( !defined('NEOCUBE_DATABASE_CONNECTIONS') ){
                exit('Constant NEOCUBE_DATABASE_CONNECTIONS not defined!');
            }
            if ( !isset(NEOCUBE_DATABASE_CONNECTIONS[$database]) or !isset(NEOCUBE_DATABASE_CONNECTIONS[$database]['adapter']) ){
                exit("Connection from {$database} not defined!");
            }
            switch (strtolower(NEOCUBE_DATABASE_CONNECTIONS[$database]['adapter'])) {
                case "mysql"  : return Mysql::getConnection(NEOCUBE_DATABASE_CONNECTIONS[$database],$database);   break;
                case "postgre": return Postgre::getConnection(NEOCUBE_DATABASE_CONNECTIONS[$database],$database); break;
                case "sqlite" : return Sqlite::getConnection(NEOCUBE_DATABASE_CONNECTIONS[$database],$database);  break;
            }
        } catch (\PDOThrowable $e) {
            Application::ErrorReporting()->dispatch($e,ErrorType::CONNECTION);
        }
    }

}
