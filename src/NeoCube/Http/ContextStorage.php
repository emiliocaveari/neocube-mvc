<?php

namespace NeoCube\Http;

class ContextStorage {

    private const KEY = 'neoCubeRequestContext';

    private static ?RequestContext $fallback = null;

    public static function current(): RequestContext {
        if (static::insideCoroutine()) {
            $store = \Swoole\Coroutine::getContext();
            if (!isset($store[self::KEY])) {
                $store[self::KEY] = new RequestContext();
            }
            return $store[self::KEY];
        }
        return static::$fallback ??= new RequestContext();
    }

    public static function set(RequestContext $context): void {
        if (static::insideCoroutine()) {
            \Swoole\Coroutine::getContext()[self::KEY] = $context;
            return;
        }
        static::$fallback = $context;
    }

    public static function clear(): void {
        if (static::insideCoroutine()) {
            unset(\Swoole\Coroutine::getContext()[self::KEY]);
            return;
        }
        static::$fallback = null;
    }

    private static function insideCoroutine(): bool {
        return class_exists(\Swoole\Coroutine::class, false) && \Swoole\Coroutine::getCid() > 0;
    }
}
