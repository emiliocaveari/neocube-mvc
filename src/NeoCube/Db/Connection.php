<?php

namespace NeoCube\Db;

use NeoCube\Application;
use NeoCube\Error\ErrorType;
use NeoCube\Db\Adapter\Mysql;
use NeoCube\Db\Adapter\Postgre;
use NeoCube\Db\Adapter\Sqlite;
use NeoCube\Env;
use NeoCube\Util\File;
use PDOException;

class Connection {

    private static array $connections = [];

    static private function getConnections() {

        if (self::$connections) return self::$connections;

        if ($json = Env::get('DATABASE_JSON_CONFIG')) {
            self::$connections = File::readJson(Env::getPath() . '/' . $json);
            return self::$connections;
        }

        if ($values = Env::get([
            'name' => 'DATABASE_NAME',
            'adapter' => 'DATABASE_ADAPTER',
            'dbname' => 'DATABASE_DBNAME',
            'host' => 'DATABASE_HOST',
            'username' => 'DATABASE_USERNAME',
            'password' => 'DATABASE_PASSWORD',
            'port' => 'DATABASE_PORT',
            'option' => 'DATABASE_OPTION'
        ])) {
            if (
                $values['name'] and
                $values['adapter'] and
                $values['dbname'] and
                $values['host'] and
                $values['username'] and
                $values['password']
            ) {
                self::$connections[$values['name']] = $values;
                return self::$connections;
            }
        }

        if (defined('NEOCUBE_DATABASE_CONNECTIONS')) {
            self::$connections = NEOCUBE_DATABASE_CONNECTIONS;
            return self::$connections;
        }

        Application::ErrorReporting()->dispatch('DATABASE CONNECTIONS not defined!', ErrorType::CONNECTION);
    }


    static public function factory(string $database = 'database_main') {
        $connections = self::getConnections();
        try {
            switch (strtolower($connections[$database]['adapter'])) {
                case "mysql":
                    return Mysql::getConnection($connections[$database], $database);
                    break;
                case "postgre":
                    return Postgre::getConnection($connections[$database], $database);
                    break;
                case "sqlite":
                    return Sqlite::getConnection($connections[$database], $database);
                    break;
            }
        } catch (PDOException $e) {
            Application::ErrorReporting()->dispatch($e, ErrorType::CONNECTION);
        }
    }
}
