<?php

namespace NeoCube\Cache;

use NeoCube\Application;
use Swoole\Database\RedisPool;

//--Cache em Redis: compartilhado entre workers/instâncias e com TTL nativo
//--(SETEX), sem precisar guardar a data de expiração junto do conteúdo.
//--Aceita um RedisPool (Swoole, uma conexão por coroutine) ou uma conexão
//--\Redis direta (FPM/Apache, um processo por requisição).
//--Falhas de conexão não derrubam a requisição: são reportadas e tratadas
//--como cache vazio.
class RedisCacheStore implements CacheStoreInterface {

    public function __construct(
        private RedisPool|\Redis $connection,
        private string $prefix = 'neocube:cache:'
    ) {
    }

    private function run(callable $callback, mixed $onFailure): mixed {
        if ($this->connection instanceof \Redis) {
            try {
                return $callback($this->connection);
            } catch (\Throwable $th) {
                Application::ErrorReporting()->dispatch($th);
                return $onFailure;
            }
        }

        try {
            $redis = $this->connection->get();
        } catch (\Throwable $th) {
            Application::ErrorReporting()->dispatch($th);
            return $onFailure;
        }
        try {
            $result = $callback($redis);
        } catch (\Throwable $th) {
            //--Conexão possivelmente quebrada: devolve null pro pool descartá-la
            //--e abrir uma nova no lugar.
            $this->connection->put(null);
            Application::ErrorReporting()->dispatch($th);
            return $onFailure;
        }
        $this->connection->put($redis);
        return $result;
    }

    public function get(string $key): mixed {
        $raw = $this->run(fn(\Redis $redis) => $redis->get($this->prefix . $key), false);
        return is_string($raw) ? @unserialize($raw) : false;
    }

    public function set(string $key, mixed $content, int $ttl): bool {
        return (bool) $this->run(
            fn(\Redis $redis) => $redis->setex($this->prefix . $key, max(1, $ttl), serialize($content)),
            false
        );
    }

    public function delete(string $key): bool {
        return $this->run(fn(\Redis $redis) => $redis->del($this->prefix . $key) !== false, false);
    }
}
