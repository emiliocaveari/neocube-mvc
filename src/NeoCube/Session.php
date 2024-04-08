<?php
namespace NeoCube;

use NeoCube\View;

class Session {

    //--Sessões
    static private $_main  = 'NEOCUBE_SESSION_DATA';
    static private $_flash = 'NEOCUBE_FLASH_MESSAGE';
    static private $_cache = 'NEOCUBE_CACHE_SESSION_DATA';

    static private $_keys  = array('FLASH_MESSAGE','NEOCUBE_SESSION_MAIN');

    private function __construct() {}

    static public function set(string $key, mixed $value) :bool {
        if ( !in_array($key,self::$_keys) ){
            $_SESSION[self::$_main][$key] = $value;
            return true;
        }
        return false;
    }

    static public function get(string $key,bool $clear=false) :mixed{
        if ( isset($_SESSION[self::$_main][$key]) ){
            $data = $_SESSION[self::$_main][$key];
            if ( $clear ) unset($_SESSION[self::$_main][$key]);
            return $data;
        }
        return false;
    }

    static public function setted(string $key) :bool {
        if ( isset($_SESSION[self::$_main][$key]) ) return true;
        return false;
    }

    static public function clear(string $key) :void{
        if ( isset($_SESSION[self::$_main][$key]) ){
            unset($_SESSION[self::$_main][$key]);
        }
    }

    //-----------------------------------------------------------//
    //--FLASH MESSAGES-------------------------------------------//
    //-----------------------------------------------------------//

    //--set Session Flash
    static public function setFlash(string|array $mensagem,string $key='default') :void {
        $_SESSION[self::$_flash] [$key] [] = $mensagem;
    }
    //--get Session Flash
    static public function flash(string|array $template,$key='default') :string {

        if ( isset( $_SESSION[self::$_flash] [$key] ) ){

            $arrMessage = $_SESSION[self::$_flash] [$key];
            unset($_SESSION[self::$_flash] [$key]);

            $htmlReturn = '';

            //--Se for array então instancia uma view
            if ( is_array($template) ){
                //--Instancindo view
                $viewName = array_shift($template);
                $viewPath = array_shift($template);
                foreach ($arrMessage as $msg) {
                    $view = new View($viewName,$viewPath);
                    //--Se for array passa valores para a view
                    if ( is_array($msg) ){
                        $view->setData($msg);
                    } else {
                        $view->setData('MESSAGE',$msg);
                    }
                    $htmlReturn .= $view->render();
                }
                //--Retorna renderização da view
                return $htmlReturn;
            }

            //--Se for string
            if ( is_string($template) ){
                foreach ($arrMessage as $msg) {
                    //--Se for array substitui o valores nas respectivas keys
                    if ( is_array($msg) ){
                        $keys   = array_keys($msg);
                        $values = array_values($msg);
                        $htmlReturn .= str_replace($keys,$values,$template);
                    } else {
                        $htmlReturn .= str_replace("MESSAGE", $msg, $template);
                    }
                }
                return $htmlReturn;
            }
        }
        return '';
    }


    //-----------------------------------------------------------//
    //--CACHE DATA-----------------------------------------------//
    //-----------------------------------------------------------//

    static public function setCache(string $key, mixed $content, string $time = '5 minutes') :void {
		$time = is_numeric($time) ? strtotime($time.' minutes') : strtotime($time);
		$content = serialize([
			'expires' => $time,
			'content' => $content
        ]);
        $_SESSION[self::$_cache][$key] = $content;
	}


	static public function getCache(string $key) :mixed {
        if ( isset($_SESSION[self::$_cache][$key]) ){
            $cache = unserialize($_SESSION[self::$_cache][$key]);
            if ($cache['expires'] > time()) {
				return $cache['content'];
			} else {
                unset($_SESSION[self::$_cache][$key]);
			}
        }
        return null;
	}

	static public function clearCache(string $key) :void {
        if ( isset($_SESSION[self::$_cache][$key]) ){
            unset($_SESSION[self::$_cache][$key]);
        }
	}


}
