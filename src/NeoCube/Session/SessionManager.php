<?php

namespace NeoCube\Session;

use NeoCube\Env;
use NeoCube\Http\RequestContext;
use Swoole\Database\RedisConfig;
use Swoole\Database\RedisPool;
use Swoole\Table;

class SessionManager {

    private static ?Table $table = null;
    private static ?RedisPool $redisPool = null;

    const COOKIE_NAME   = 'NEOCUBE_SID';
    const TTL_SECONDS   = 4 * 3600;
    const MAX_ROWS      = 4096;
    const MAX_ROW_BYTES = 65536;

    //--Chamado uma única vez, no processo master, antes de $server->start() —
    //--Swoole\Table é memória compartilhada e precisa existir antes do fork
    //--dos workers pra ser herdada por todos eles.
    public static function createTable(): Table {
        $table = new Table(self::MAX_ROWS);
        $table->column('data', Table::TYPE_STRING, self::MAX_ROW_BYTES);
        $table->column('last_access', Table::TYPE_INT);
        $table->create();
        return $table;
    }

    public static function useTable(Table $table): void {
        self::$table = $table;
    }

    //--Chamado uma vez por worker, dentro de onWorkerStart — diferente da
    //--Swoole\Table, o pool usa Swoole\Coroutine\Channel por baixo, que não
    //--sobrevive a fork, então só pode ser criado DEPOIS dele.
    public static function createRedisPool(): RedisPool {
        $config = (new RedisConfig())
            ->withHost(Env::get('REDIS_HOST', 'redis'))
            ->withPort((int) Env::get('REDIS_PORT', '6379'));
        return new RedisPool($config);
    }

    public static function useRedisPool(RedisPool $pool): void {
        self::$redisPool = $pool;
    }

    public static function isTableMode(): bool {
        return self::$table !== null;
    }

    public static function isRedisMode(): bool {
        return self::$redisPool !== null;
    }

    public static function create(RequestContext $ctx): SessionStoreInterface {
        if (!self::isTableMode() && !self::isRedisMode()) {
            return new NativePhpSessionStore();
        }

        $sessionId = $ctx->cookies[self::COOKIE_NAME] ?? null;

        if (self::isRedisMode()) {
            if (!$sessionId) $sessionId = bin2hex(random_bytes(16));
            $ctx->addCookie(self::COOKIE_NAME, $sessionId, time() + self::TTL_SECONDS, '/', false);
            return new RedisSessionStore(self::$redisPool, $sessionId, self::TTL_SECONDS);
        }

        if (!$sessionId || !self::$table->exist($sessionId)) {
            $sessionId = bin2hex(random_bytes(16));
        }

        $ctx->addCookie(self::COOKIE_NAME, $sessionId, time() + self::TTL_SECONDS, '/', false);

        return new TableSessionStore(self::$table, $sessionId);
    }

    //--Roda periodicamente (worker 0) removendo sessões inativas há mais de
    //--TTL_SECONDS. Só se aplica ao modo Swoole\Table — no Redis a expiração
    //--é nativa (SETEX/EXPIRE), não precisa de varredura manual.
    public static function gc(): void {
        if (!self::$table) return;
        $expireBefore = time() - self::TTL_SECONDS;
        foreach (self::$table as $sessionId => $row) {
            if ($row['last_access'] < $expireBefore) self::$table->del($sessionId);
        }
    }
}
