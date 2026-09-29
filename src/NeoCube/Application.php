<?php

namespace NeoCube;

use NeoCube\Application\ApplicationContextStorage;
use NeoCube\Error\ErrorAbstract;
use NeoCube\Error\ErrorType;
use NeoCube\Error;
use NeoCube\Http\ContextStorage;

final class Application{

    private function __construct(){}

    //--AMBIENTE
    const string ENVIRONMENT_DEV = 'DEV';
    const string ENVIRONMENT_PRODUCTION = 'PRODUCTION';

    static public function setEnvironment(string $environment){
        Env::setValue('ENVIRONMENT',$environment);
    }
    static public function isEnvironment(string $environment){
        return (Env::get('ENVIRONMENT',self::ENVIRONMENT_PRODUCTION) === $environment);
    }

    static public function setRouter(Router $Router){
        ApplicationContextStorage::current()->router = $Router;
    }
    static public function Router(): ?Router {
        return ApplicationContextStorage::current()->router;
    }

    static public function setErrorReporting(ErrorAbstract $Error){
        ApplicationContextStorage::current()->error = $Error;
    }
    static public function ErrorReporting() : ErrorAbstract{
        $ctx = ApplicationContextStorage::current();
        if (!$ctx->error) $ctx->error = new Error();
        return $ctx->error;
    }
    static public function startErrorReporting() : void {

        set_error_handler(array('NeoCube\Error\Controller','handler'));

        //--Sob Swoole o processo é de vida longa: register_shutdown_function só dispara
        //--quando o WORKER morre, não a cada requisição, então é registrado uma única
        //--vez no boot do worker (ver server.php) em vez de aqui.
        if (!defined('NEOCUBE_SWOOLE')) {
            register_shutdown_function(array('NeoCube\Error\Controller','shutdown'));
        }

        $server = ContextStorage::current()->server;
        if (($server['REQUEST_METHOD'] ?? null) == 'POST' and ($server['CONTENT_LENGTH'] ?? 0) > 0 ) {
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
            if (  $server['CONTENT_LENGTH'] > $displayMaxSize ){
                $error = array(
                    'code'    => 'POST_MAX_SIZE',
                    'message' => "Posted data is too large. {$server['CONTENT_LENGTH']}  bytes exceeds the maximum size of {$displayMaxSize} bytes.",
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

        $router = $Router ?? new Router();
        static::setRouter($router);

        $router->routing();
        $router->writeOut();
    }

}
