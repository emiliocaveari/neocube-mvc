<?php

namespace NeoCube\Session;

use Swoole\Table;

//--Usada no modo Swoole: sessão guardada em memória compartilhada entre os
//--workers do mesmo servidor (Swoole\Table), já que session_start() nativo
//--(baseado em lock de arquivo) não se comporta bem em processo persistente.
class TableSessionStore implements SessionStoreInterface {

    private array $data;
    private bool $dirty = false;

    public function __construct(
        private Table $table,
        private string $sessionId
    ) {
        $row = $table->get($sessionId);
        $this->data = ($row && !empty($row['data'])) ? (json_decode($row['data'], true) ?: []) : [];
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
        //--Sempre atualiza o last_access (usado pelo GC), mesmo sem mudança de dado.
        $this->table->set($this->sessionId, [
            'data'        => $this->dirty ? json_encode($this->data) : ($this->table->get($this->sessionId)['data'] ?? '{}'),
            'last_access' => time(),
        ]);
    }
}
