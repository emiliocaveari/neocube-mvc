<?php

namespace NeoCube;

use NeoCube\Http\ContextStorage;

class Cookie {

    private function __construct() {}


    static public function set(string $key, string $value, int|string $expire, string $path = '/'): bool {
        $expire = is_int($expire) ? time() + $expire : strtotime($expire);
        ContextStorage::current()->addCookie($key, $value, $expire, $path, false);
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

    static public function clear(string $key, string $path = '/'): bool {
        $ctx = ContextStorage::current();
        if (isset($ctx->cookies[$key])) {
            $ctx->addCookie($key, '', time() - 3600, $path, true);
        }
        return true;
    }
}
