<?php

namespace NeoCube\Application;

//--Router/Error ativos, isolados por coroutine (Swoole) ou por requisição
//--(FPM/CLI, via fallback estático). Separado do NeoCube\Http\ContextStorage
//--porque router/error são estado de aplicação, não dados de entrada da
//--requisição HTTP — RequestContext guarda só o segundo.
class ApplicationContextStorage {

    private const KEY = 'neoCubeApplicationContext';

    private static ?ApplicationContext $fallback = null;

    public static function current(): ApplicationContext {
        if (static::insideCoroutine()) {
            $store = \Swoole\Coroutine::getContext();
            if (!isset($store[self::KEY])) {
                $store[self::KEY] = new ApplicationContext();
            }
            return $store[self::KEY];
        }
        return static::$fallback ??= new ApplicationContext();
    }

    public static function set(ApplicationContext $context): void {
        if (static::insideCoroutine()) {
            \Swoole\Coroutine::getContext()[self::KEY] = $context;
            return;
        }
        static::$fallback = $context;
    }

    private static function insideCoroutine(): bool {
        return class_exists(\Swoole\Coroutine::class, false) && \Swoole\Coroutine::getCid() > 0;
    }
}
