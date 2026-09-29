<?php

namespace NeoCube;

use NeoCube\Http\ContextStorage;
use NeoCube\Session\SessionStoreInterface;
use NeoCube\View;

class Session {

    //--Sessões
    static private $_main  = 'NEOCUBE_SESSION_DATA';
    static private $_flash = 'NEOCUBE_FLASH_MESSAGE';
    static private $_cache = 'NEOCUBE_CACHE_SESSION_DATA';

    private function __construct() {
    }

    static protected function store(): SessionStoreInterface {
        return ContextStorage::current()->sessionStore();
    }

    static public function set(string $key, mixed $value): void {
        static::store()->set(self::$_main, $key, $value);
    }

    static public function get(string $key, bool $clear = false): mixed {
        $store = static::store();
        if ($store->has(self::$_main, $key)) {
            $data = $store->get(self::$_main, $key);
            if ($clear) $store->unset(self::$_main, $key);
            return $data;
        }
        return false;
    }

    static public function has(string $key): bool {
        return static::store()->has(self::$_main, $key);
    }

    static public function clear(string $key): void {
        static::store()->unset(self::$_main, $key);
    }

    //-----------------------------------------------------------//
    //--FLASH MESSAGES-------------------------------------------//
    //-----------------------------------------------------------//

    //--set Session Flash
    static public function setFlash(string|array $mensagem, string $key = 'default'): void {
        $store = static::store();
        $list = $store->get(self::$_flash, $key) ?: [];
        $list[] = $mensagem;
        $store->set(self::$_flash, $key, $list);
    }
    static public function hasFlash(string $key = 'default'): bool {
        return static::store()->has(self::$_flash, $key);
    }
    //--get Session Flash
    static public function getFlash(string $key = 'default', bool $remove = true): string|array {
        $store = static::store();
        $arrMessage = $store->get(self::$_flash, $key) ?: '';
        if ($arrMessage and $remove) $store->unset(self::$_flash, $key);
        return $arrMessage;
    }
    //--get Session Flash
    static public function flash(string|array $template, $key = 'default'): string {

        $store = static::store();

        if ($store->has(self::$_flash, $key)) {

            $arrMessage = $store->get(self::$_flash, $key);
            $store->unset(self::$_flash, $key);

            $htmlReturn = '';

            //--Se for array então instancia uma view
            if (is_array($template)) {
                //--Instancindo view
                $viewName = array_shift($template);
                $viewPath = array_shift($template);
                foreach ($arrMessage as $msg) {
                    $view = new View($viewName, $viewPath);
                    //--Se for array passa valores para a view
                    if (is_array($msg)) {
                        $view->setData($msg);
                    } else {
                        $view->setData('MESSAGE', $msg);
                    }
                    $htmlReturn .= $view->render();
                }
                //--Retorna renderização da view
                return $htmlReturn;
            }

            //--Se for string
            if (is_string($template)) {
                foreach ($arrMessage as $msg) {
                    //--Se for array substitui o valores nas respectivas keys
                    if (is_array($msg)) {
                        $keys   = array_keys($msg);
                        $values = array_values($msg);
                        $htmlReturn .= str_replace($keys, $values, $template);
                    } else {
                        $htmlReturn .= str_replace("MESSAGE", $msg, $template);
                    }
                }
                return $htmlReturn;
            }
        }
        return '';
    }

    //--clear Session Flash
    static public function clearFlash(?string $key = null): void {
        if ($key !== null) static::store()->unset(self::$_flash, $key);
        else static::store()->unsetBucket(self::$_flash);
    }



    //-----------------------------------------------------------//
    //--CACHE DATA-----------------------------------------------//
    //-----------------------------------------------------------//

    static public function setCache(string $key, mixed $content, string $time = '5 minutes'): void {
        $time = is_numeric($time) ? strtotime($time . ' minutes') : strtotime($time);
        $content = serialize([
            'expires' => $time,
            'content' => $content
        ]);
        static::store()->set(self::$_cache, $key, $content);
    }


    static public function getCache(string $key): mixed {
        $store = static::store();
        if ($store->has(self::$_cache, $key)) {
            $cache = unserialize($store->get(self::$_cache, $key));
            if ($cache['expires'] > time()) {
                return $cache['content'];
            } else {
                $store->unset(self::$_cache, $key);
            }
        }
        return null;
    }

    static public function clearCache(string $key): void {
        static::store()->unset(self::$_cache, $key);
    }
}
