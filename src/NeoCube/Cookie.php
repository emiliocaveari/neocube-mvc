<?php

namespace NeoCube;

class Cookie {

    //--Construtor privado
    private function __construct() {}


    static public function set(string $key,string $value,int|string $expire, string $path='/') : bool {
        if ( is_int($expire)) $expire = time() + $expire;
        else $expire = strtotime($expire);

        return setcookie($key,$value,$expire,$path);
    }

    static public function get(string $key, bool $clear=false) : string | false{
        if ( isset($_COOKIE[$key]) ){
            $data = $_COOKIE[$key];
            if ( $clear ) setcookie($key);
            return $data;
        }
        return false;
    }

    static public function setted(string $key) : bool {
        if (isset($_COOKIE[$key])) return true;
        return false;
    }

    static public function clear(string $key, string $path='/') : bool {
        if (isset($_COOKIE[$key])) return setcookie($key,'',time() - 3600, $path);
        else return true;
    }

}
