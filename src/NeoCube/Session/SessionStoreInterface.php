<?php

namespace NeoCube\Session;

interface SessionStoreInterface {

    public function get(string $bucket, string $key): mixed;

    public function has(string $bucket, string $key): bool;

    public function set(string $bucket, string $key, mixed $value): void;

    public function unset(string $bucket, string $key): void;

    public function unsetBucket(string $bucket): void;

    //--Persiste a sessão no backend. Chamado ao final de cada requisição.
    public function persist(): void;
}
