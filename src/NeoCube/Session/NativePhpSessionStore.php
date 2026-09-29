<?php

namespace NeoCube\Session;

//--Usada em FPM/CLI: mantém o comportamento atual, baseado em session_start()/$_SESSION.
class NativePhpSessionStore implements SessionStoreInterface {

    public function get(string $bucket, string $key): mixed {
        return $_SESSION[$bucket][$key] ?? null;
    }

    public function has(string $bucket, string $key): bool {
        return isset($_SESSION[$bucket][$key]);
    }

    public function set(string $bucket, string $key, mixed $value): void {
        $_SESSION[$bucket][$key] = $value;
    }

    public function unset(string $bucket, string $key): void {
        unset($_SESSION[$bucket][$key]);
    }

    public function unsetBucket(string $bucket): void {
        unset($_SESSION[$bucket]);
    }

    public function persist(): void {
        //--PHP persiste a sessão nativa sozinho no shutdown do processo (FPM).
    }
}
