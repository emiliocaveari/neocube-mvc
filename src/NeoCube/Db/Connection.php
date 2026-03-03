<?php

namespace NeoCube\Db;

use NeoCube\Application;
use NeoCube\Error\ErrorType;
use NeoCube\Db\Adapter\Mysql;
use NeoCube\Db\Adapter\Postgre;
use NeoCube\Db\Adapter\Sqlite;
use NeoCube\Env;
use NeoCube\Util\File;
use PDO;
use PDOException;

class Connection {

    private static array $connections = [];

    static private function getConnections(): ?array {
        if (self::$connections) return self::$connections;
        if ($json = Env::get('NEOCUBE_DATABASE_JSON')) {
            self::$connections = File::readJson($json);
            return self::$connections;
        }
        return Application::ErrorReporting()->dispatch('DATABASE CONNECTIONS not defined!', ErrorType::CONNECTION);
    }


    static public function factory(?string $database = null): ?PDO {

        $connections = self::getConnections();

        if ($database) {
            if (!isset($connections[$database]['adapter']))
                return Application::ErrorReporting()->dispatch("DATABASE \"$database\" CONNECTIONS not defined!", ErrorType::CONNECTION);
            $conn = $connections[$database];
        } else {
            $database = 'main';
            $conn = reset($connections);
        }

        try {
            return match (strtolower($conn['adapter'])) {
                "mysql" => Mysql::getConnection($conn, $database),
                "postgre" => Postgre::getConnection($conn, $database),
                "sqlite" => Sqlite::getConnection($conn, $database)
            };
        } catch (PDOException $e) {
            return Application::ErrorReporting()->dispatch($e, ErrorType::CONNECTION);
        }
    }
}
