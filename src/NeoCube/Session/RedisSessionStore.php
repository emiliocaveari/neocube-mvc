<?php

namespace NeoCube\Session;

use Swoole\Database\RedisPool;

//--Alternativa ao Swoole\Table (ver TableSessionStore): sessão em Redis,
//--persiste entre restarts do container e pode ser compartilhada entre
//--múltiplas instâncias da aplicação. TTL é nativo do Redis (EXPIRE/SETEX),
//--não precisa de um timer de GC como o Swoole\Table.
class RedisSessionStore implements SessionStoreInterface {

    use TracksSessionChanges;

    const KEY_PREFIX = 'neocube:session:';

    //--regenerate(): a chave nova recebe os dados inteiros, não só as alterações
    private bool $replaceAll = false;

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
        $this->data = $this->decode($raw ?: null);
    }

    //--A sessão existe no Redis? (id enviado pelo cliente só vale se existir)
    public static function exists(RedisPool $pool, string $sessionId): bool {
        $redis = $pool->get();
        try {
            return (bool) $redis->exists(self::KEY_PREFIX . $sessionId);
        } finally {
            $pool->put($redis);
        }
    }

    private function key(): string {
        return self::KEY_PREFIX . $this->sessionId;
    }

    public function regenerate(): ?string {
        $redis = $this->pool->get();
        try {
            $redis->del($this->key());
        } finally {
            $this->pool->put($redis);
        }
        $this->sessionId = $this->newSessionId();
        $this->replaceAll = true;
        return $this->sessionId;
    }

    public function persist(): void {
        $redis = $this->pool->get();
        try {
            if ($this->replaceAll) {
                $redis->setex($this->key(), $this->ttl, json_encode($this->data));
            } else if ($this->changes) {
                $latest = $this->decode($redis->get($this->key()) ?: null);
                $redis->setex($this->key(), $this->ttl, json_encode($this->applyChanges($latest)));
            } else {
                //--Nada mudou: só renova o TTL (sliding expiration) sem reescrever o valor.
                $redis->expire($this->key(), $this->ttl);
            }
        } finally {
            $this->pool->put($redis);
        }
    }
}
