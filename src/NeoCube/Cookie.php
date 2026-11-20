<?php

namespace NeoCube;

use NeoCube\Http\ContextStorage;

class Cookie {

    private function __construct() {}


    //--$options (opcional): 'httponly' => bool, 'secure' => bool,
    //--'samesite' => 'Lax'|'Strict'|'None', 'domain' => string
    static public function set(string $key, string $value, int|string $expire, string $path = '/', array $options = []): bool {
        $expire = is_int($expire) ? time() + $expire : strtotime($expire);
        ContextStorage::current()->addCookie($key, $value, $expire, $path, false, $options);
        return true;
    }

    static public function get(string $key, bool $clear = false): string|false {
        $ctx = ContextStorage::current();
        if (isset($ctx->cookies[$key])) {
            $data = $ctx->cookies[$key];
            if ($clear) static::clear($key);
            return $data;
        }
        return false;
    }

    static public function has(string $key): bool {
        return isset(ContextStorage::current()->cookies[$key]);
    }

    //--Para apagar, path e domain precisam ser os mesmos usados no set()
    static public function clear(string $key, string $path = '/', array $options = []): bool {
        $ctx = ContextStorage::current();
        if (isset($ctx->cookies[$key])) {
            $ctx->addCookie($key, '', time() - 3600, $path, true, $options);
        }
        return true;
    }
}
