<?php

namespace NeoCube\Session;

//--Dados da sessão em memória + registro do que esta requisição alterou. Ao
//--persistir, as alterações são aplicadas sobre o valor mais recente do backend
//--(e não o valor inteiro lido no início): duas requisições paralelas na mesma
//--sessão, como um AJAX e uma página, não apagam o que a outra gravou.
trait TracksSessionChanges {

    private array $data = [];
    private array $changes = [];

    public function get(string $bucket, string $key): mixed {
        return $this->data[$bucket][$key] ?? null;
    }

    public function has(string $bucket, string $key): bool {
        return isset($this->data[$bucket][$key]);
    }

    public function set(string $bucket, string $key, mixed $value): void {
        $this->data[$bucket][$key] = $value;
        $this->changes[] = [$bucket, $key, $value];
    }

    public function unset(string $bucket, string $key): void {
        unset($this->data[$bucket][$key]);
        $this->changes[] = [$bucket, $key];
    }

    public function unsetBucket(string $bucket): void {
        unset($this->data[$bucket]);
        $this->changes[] = [$bucket];
    }

    private function decode(?string $raw): array {
        return $raw ? (json_decode($raw, true) ?: []) : [];
    }

    //--Aplica as alterações desta requisição sobre $latest e devolve o resultado
    private function applyChanges(array $latest): array {
        foreach ($this->changes as $change) {
            if (count($change) === 3) $latest[$change[0]][$change[1]] = $change[2];
            else if (count($change) === 2) unset($latest[$change[0]][$change[1]]);
            else unset($latest[$change[0]]);
        }
        return $latest;
    }

    private function newSessionId(): string {
        return bin2hex(random_bytes(16));
    }
}
