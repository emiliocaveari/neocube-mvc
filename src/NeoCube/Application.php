<?php

namespace NeoCube;

use NeoCube\Error\ErrorAbstract;
use NeoCube\Error\ErrorType;
use NeoCube\Error;

class Application{

    private function __construct(){}

    //--AMBIENTE
    const ENVIRONMENT_TEST = 0;
    const ENVIRONMENT_PRODUCTION = 1;

    static private ?Router $router = null;
    static private ?ErrorAbstract $_Error = null;
    static private $_environment = self::ENVIRONMENT_PRODUCTION;
    

    static public function setEnvironment($environment){
        static::$_environment = $environment;
    }
    static public function isEnvironment($environment){
        return (static::$_environment === $environment);
    }

    //--Router
    static public function setRouter(Router $Router){
        static::$router = $Router;
    }
    static public function Router(){
        return static::$router;
    }
    
    //--ERROS
    static public function setErrorReporting(ErrorAbstract $Error){
        static::$_Error = $Error;
    }
    static public function ErrorReporting() : ErrorAbstract{
        return static::$_Error;
    }
    static public function startErrorReporting() : void {
        if (!static::$_Error) static::$_Error = new Error();

        set_error_handler(array('NeoCube\Error\Controller','handler'));
        register_shutdown_function(array('NeoCube\Error\Controller','shutdown'));

        //--Tratamento de envio de arquivo maior que o permitido pela configuração do PHP_INI
        if ($_SERVER['REQUEST_METHOD'] == 'POST' AND isset($_SERVER['CONTENT_LENGTH']) and $_SERVER['CONTENT_LENGTH'] > 0 ) {
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
                static::$_Error->dispatch($error,ErrorType::STARTING);
            }
        }
    }
    


    //--Estrutura em modelos
    static public function startModules(?Router $Router = null){
        static::startErrorReporting();

        if ($Router) static::$router = $Router;

        static::$router->routing();
        static::$router->writeOut();
    }

}
