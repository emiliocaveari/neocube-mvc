<?php

namespace NeoCube\Db\Adapter;

use NeoCube\Http\ContextStorage;
use PDO;
use Swoole\Database\PDOConfig;
use Swoole\Database\PDOPool;
use Swoole\Database\PDOProxy;

class Postgre implements AdapterInterface {

    //--FPM/CLI: uma conexão por processo, reaproveitada pra vida toda do worker/processo.
    private static array $instance = [];

    //--Swoole: um pool por banco, compartilhado por todo o worker; cada coroutine
    //--empresta uma conexão do pool e devolve ao final da requisição (ver Kernel).
    private static array $pools = [];

    static function getConnection($db, $database): PDO|PDOProxy {
        return static::isSwoole()
            ? static::getPooledConnection($db, $database)
            : static::getPersistentConnection($db, $database);
    }

    static private function isSwoole(): bool {
        return defined('NEOCUBE_SWOOLE')
            && class_exists(\Swoole\Coroutine::class, false)
            && \Swoole\Coroutine::getCid() > 0;
    }

    static private function getPersistentConnection($db, $database): PDO {
        if (!isset(self::$instance[$database])) {
            self::$instance[$database] = static::connect($db);
        }
        return self::$instance[$database];
    }

    static private function getPooledConnection($db, $database): PDOProxy {
        $ctx = ContextStorage::current();

        //--Já emprestada nesta coroutine/requisição? reaproveita.
        if (isset($ctx->dbConnections[$database])) {
            return $ctx->dbConnections[$database];
        }

        if (!isset(self::$pools[$database])) {
            $config = (new PDOConfig())
                ->withDriver('pgsql')
                ->withHost($db['host'])
                ->withPort((int) ($db['port'] ?? 5432))
                ->withDbName($db['dbname'])
                ->withUsername($db['username'])
                ->withPassword($db['password'])
                ->withOptions($db['options'] ?? []);
            self::$pools[$database] = new PDOPool($config);
        }

        $pdo = self::$pools[$database]->get();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $ctx->dbConnections[$database] = $pdo;
        $ctx->dbPoolsBorrowed[$database] = self::$pools[$database];

        return $pdo;
    }

    static private function connect($db): PDO {
        $PDO = new PDO("pgsql:dbname={$db['dbname']} host={$db['host']}", $db['username'], $db['password']);
        $PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $PDO;
    }
}
