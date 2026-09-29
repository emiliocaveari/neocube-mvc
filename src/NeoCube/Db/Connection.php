<?php

namespace NeoCube\Db;

use Exception;
use NeoCube\Application;
use NeoCube\Error\ErrorType;
use NeoCube\Db\Adapter\Mysql;
use NeoCube\Db\Adapter\Postgre;
use NeoCube\Db\Adapter\Sqlite;
use NeoCube\Env;
use NeoCube\Http\RequestContext;
use NeoCube\Util\File;
use PDO;
use PDOException;
use Swoole\Database\PDOProxy;

class Connection {

    private static array $connections = [];

    static private function getConnections(): ?array {
        if (self::$connections) return self::$connections;
        if ($json = Env::get('NEOCUBE_DATABASE_JSON')) {
            self::$connections = File::readJson($json);
            return self::$connections;
        }
        throw new Exception('DATABASE CONNECTIONS not defined!', 1);
    }

    //--Resolução puramente funcional (sem "banco atual" mutável): sob Swoole um
    //--static mutável aqui persistiria pelo worker inteiro, vazando entre
    //--coroutines concorrentes e até entre requisições seguintes.
    static private function resolveConnection(?string $database): array {
        $connections = self::getConnections();
        $database ??= Env::get('NEOCUBE_DEFAULT_DATABASE') ?: array_key_first($connections);
        if (!isset($connections[$database]['adapter']))
            throw new Exception("DATABASE \"$database\" CONNECTIONS not defined!", 1);
        return [$database, $connections[$database]];
    }

    static public function factory(?string $database = null): PDO|PDOProxy|null {
        try {
            [$database, $conn] = self::resolveConnection($database);
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

    //--Adapter ("mysql", "postgre", "sqlite") configurado para a conexão, usado
    //--pelo Mapper para escolher o Query\Builder no mesmo dialeto do banco.
    static public function getAdapter(?string $database = null): string {
        try {
            [, $conn] = self::resolveConnection($database);
            return strtolower($conn['adapter']);
        } catch (Exception $e) {
            return Application::ErrorReporting()->dispatch($e, ErrorType::INTERNAL) ?? 'mysql';
        }
    }

    //--Devolve aos pools (modo Swoole) todas as conexões emprestadas durante a
    //--requisição atual, não importa qual adapter foi usado. Chamado no finally
    //--do Kernel, uma vez por requisição.
    static public function releaseAll(RequestContext $ctx): void {
        foreach ($ctx->dbConnections as $database => $pdo) {
            if (isset($ctx->dbPoolsBorrowed[$database])) {
                $ctx->dbPoolsBorrowed[$database]->put($pdo);
            }
        }
        $ctx->dbConnections = [];
        $ctx->dbPoolsBorrowed = [];
    }
}
