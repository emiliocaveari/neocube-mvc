<?php

namespace NeoCube\Session;

use Swoole\Database\RedisPool;

//--Alternativa ao Swoole\Table (ver TableSessionStore): sessão em Redis,
//--persiste entre restarts do container e pode ser compartilhada entre
//--múltiplas instâncias da aplicação. TTL é nativo do Redis (EXPIRE/SETEX),
//--não precisa de um timer de GC como o Swoole\Table.
class RedisSessionStore implements SessionStoreInterface {

    private array $data;
    private bool $dirty = false;

    public function __construct(
        private RedisPool $pool,
        private string $sessionId,
        private int $ttl
    ) {
        $redis = $this->pool->get();
        try {
            $raw = $redis->get($this->key());
        } finally {
            $this->pool->put($redis);
        }
        $this->data = $raw ? (json_decode($raw, true) ?: []) : [];
    }

    private function key(): string {
        return 'neocube:session:' . $this->sessionId;
    }

    public function get(string $bucket, string $key): mixed {
        return $this->data[$bucket][$key] ?? null;
    }

    public function has(string $bucket, string $key): bool {
        return isset($this->data[$bucket][$key]);
    }

    public function set(string $bucket, string $key, mixed $value): void {
        $this->data[$bucket][$key] = $value;
        $this->dirty = true;
    }

    public function unset(string $bucket, string $key): void {
        unset($this->data[$bucket][$key]);
        $this->dirty = true;
    }

    public function unsetBucket(string $bucket): void {
        unset($this->data[$bucket]);
        $this->dirty = true;
    }

    public function persist(): void {
        $redis = $this->pool->get();
        try {
            if ($this->dirty) {
                $redis->setex($this->key(), $this->ttl, json_encode($this->data));
            } else {
                //--Nada mudou: só renova o TTL (sliding expiration) sem reescrever o valor.
                $redis->expire($this->key(), $this->ttl);
            }
        } finally {
            $this->pool->put($redis);
        }
    }
}
