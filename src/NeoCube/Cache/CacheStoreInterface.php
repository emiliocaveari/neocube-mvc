<?php

namespace NeoCube\Cache;

interface CacheStoreInterface {

    //--Retorna false quando a chave não existe ou expirou.
    public function get(string $key): mixed;

    public function set(string $key, mixed $content, int $ttl): bool;

    public function delete(string $key): bool;
}
