<?php

namespace NeoCube\Cache;

use NeoCube\Application;
use NeoCube\Env;
use Swoole\Database\RedisConfig;
use Swoole\Database\RedisPool;

//--Escolhe o backend do NeoCube\Cache, conforme CACHE_DRIVER ("file" ou "redis"):
//--  - Swoole: modo Redis quando um pool é registrado no onWorkerStart;
//--  - FPM/Apache: modo Redis via conexão \Redis persistente (pconnect);
//--  - demais casos, ou Redis inacessível: arquivos em NEOCUBE_CACHE_PATH.
class CacheManager {

    private static ?RedisPool $redisPool = null;
    private static ?\Redis $redisConnection = null;

    //--Chamado uma vez por worker, dentro de onWorkerStart — assim como no
    //--SessionManager, o pool usa Swoole\Coroutine\Channel, que não
    //--sobrevive a fork. Usa um DB index próprio (padrão 1) pra isolar as
    //--chaves do cache das sessões e permitir um FLUSHDB só do cache.
    public static function createRedisPool(): RedisPool {
        $config = (new RedisConfig())
            ->withHost(Env::get('REDIS_HOST', 'redis'))
            ->withPort((int) Env::get('REDIS_PORT', '6379'))
            ->withDbIndex(self::dbIndex());
        return new RedisPool($config);
    }

    public static function useRedisPool(?RedisPool $pool): void {
        self::$redisPool = $pool;
    }

    public static function isRedisMode(): bool {
        return self::$redisPool !== null;
    }

    private static function dbIndex(): int {
        return (int) Env::get('CACHE_REDIS_DB', '1');
    }

    //--Fora do Swoole: pconnect reaproveita o socket entre requisições do
    //--mesmo processo FPM. Não é usado sob Swoole, onde uma conexão estática
    //--seria compartilhada entre coroutines concorrentes.
    private static function redisConnection(): ?\Redis {
        if (defined('NEOCUBE_SWOOLE') || !extension_loaded('redis')) return null;
        if (self::$redisConnection) return self::$redisConnection;
        try {
            $redis = new \Redis();
            if (!$redis->pconnect(Env::get('REDIS_HOST', 'redis'), (int) Env::get('REDIS_PORT', '6379'), 1.0)) {
                return null;
            }
            $redis->select(self::dbIndex());
            return self::$redisConnection = $redis;
        } catch (\Throwable $th) {
            Application::ErrorReporting()->dispatch($th);
            return null;
        }
    }

    public static function create(?string $folder = null): CacheStoreInterface {
        if (self::isRedisMode()) {
            return new RedisCacheStore(self::$redisPool);
        }
        if (Env::get('CACHE_DRIVER', 'file') === 'redis' && ($redis = self::redisConnection())) {
            return new RedisCacheStore($redis);
        }
        $folder = $folder ?: (Env::getValue('NEOCUBE_CACHE_PATH') ?: sys_get_temp_dir());
        return new FileCacheStore($folder);
    }
}
