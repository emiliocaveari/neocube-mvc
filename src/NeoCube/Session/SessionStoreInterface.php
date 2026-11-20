<?php

namespace NeoCube\Session;

interface SessionStoreInterface {

    public function get(string $bucket, string $key): mixed;

    public function has(string $bucket, string $key): bool;

    public function set(string $bucket, string $key, mixed $value): void;

    public function unset(string $bucket, string $key): void;

    public function unsetBucket(string $bucket): void;

    //--Troca o id mantendo os dados e invalida o id anterior (ver Session::regenerate()).
    //--Devolve o id novo, ou null se o backend já cuida do cookie (sessão nativa).
    public function regenerate(): ?string;

    //--Persiste a sessão no backend. Chamado ao final de cada requisição.
    public function persist(): void;
}
