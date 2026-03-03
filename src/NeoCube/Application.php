<?php

namespace NeoCube;

use NeoCube\Error\ErrorAbstract;
use NeoCube\Error\ErrorType;
use NeoCube\Error;

final class Application{

    private function __construct(){}

    //--AMBIENTE
    const ENVIRONMENT_DEV = 'DEV';
    const ENVIRONMENT_PRODUCTION = 'PRODUCTION';

    static private ?Router $router = null;
    static private ?ErrorAbstract $_Error = null;

    static public function setEnvironment($environment){
        Env::setValue('ENVIRONMENT',$environment);
    }
    static public function isEnvironment($environment){
        $env = Env::getValue('ENVIRONMENT');
        return (Env::getValue('ENVIRONMENT') === $environment);
    }

    static public function setRouter(Router $Router){
        static::$router = $Router;
    }
    static public function Router(){
        return static::$router;
    }
    
    static public function setErrorReporting(ErrorAbstract $Error){
        static::$_Error = $Error;
    }
    static public function ErrorReporting() : ErrorAbstract{
        if (!static::$_Error) static::$_Error = new Error();
        return static::$_Error;
    }
    static public function startErrorReporting() : void {

        set_error_handler(array('NeoCube\Error\Controller','handler'));
        register_shutdown_function(array('NeoCube\Error\Controller','shutdown'));

        if (isset($_SERVER['REQUEST_METHOD']) and $_SERVER['REQUEST_METHOD'] == 'POST' and isset($_SERVER['CONTENT_LENGTH']) and $_SERVER['CONTENT_LENGTH'] > 0 ) {
            $displayMaxSize = ini_get('post_max_size');
            $size = null;
            if (!is_numeric($displayMaxSize)){
                $size           = substr($displayMaxSize,-1);
                $displayMaxSize = substr($displayMaxSize,0,-1);
                switch ( $size ){
                    case 'G': $displayMaxSize = $displayMaxSize * 1024;
                    case 'M': $displayMaxSize = $displayMaxSize * 1024;
                    case 'K': $displayMaxSize = $displayMaxSize * 1024;
                }
            }
            if (  $_SERVER['CONTENT_LENGTH'] > $displayMaxSize ){
                $error = array(
                    'code'    => 'POST_MAX_SIZE',
                    'message' => "Posted data is too large. {$_SERVER['CONTENT_LENGTH']}  bytes exceeds the maximum size of {$displayMaxSize} bytes.",
                    'file'    => 'Undefined',
                    'line'    => 0,
                    'context' => null
                );
                static::ErrorReporting()->dispatch($error,ErrorType::STARTING);
            }
        }
    }

    static public function startModules(?Router $Router = null){
        static::startErrorReporting();

        static::$router = $Router ?? new Router();

        static::$router->routing();
        static::$router->writeOut();
    }

}
