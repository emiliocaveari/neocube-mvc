<?php

namespace NeoCube\Db;

use Exception;
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
    private static string $database = 'main';

    static private function getConnections(): ?array {
        if (self::$connections) return self::$connections;
        if ($json = Env::get('NEOCUBE_DATABASE_JSON')) {
            self::$connections = File::readJson($json);
            return self::$connections;
        }
        throw new Exception('DATABASE CONNECTIONS not defined!', 1);
    }

    static public function factory(?string $database = null): ?PDO {
        try {
            $connections = self::getConnections();
            if ($database) {
                if (!isset($connections[$database]['adapter']))
                    throw new Exception("DATABASE \"$database\" CONNECTIONS not defined!", 1);
                self::$database = $database;
                $conn = $connections[$database];
            } else if (isset($connections[self::$database])) {
                $database = self::$database;
                $conn = $connections[$database];
            } else {
                $database = array_key_first($connections);
                $conn = reset($connections);
            }
            return match (strtolower($conn['adapter'])) {
                "mysql" => Mysql::getConnection($conn, $database),
                "postgre" => Postgre::getConnection($conn, $database),
                "sqlite" => Sqlite::getConnection($conn, $database)
            };
        } catch (PDOException $e) {
            return Application::ErrorReporting()->dispatch($e, ErrorType::CONNECTION);
        } catch (Exception $e) {
            return Application::ErrorReporting()->dispatch($e, ErrorType::INTERNAL);
        }
    }
}
