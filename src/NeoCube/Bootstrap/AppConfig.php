<?php

namespace NeoCube\Bootstrap;

use InvalidArgumentException;
use NeoCube\Error\ErrorAbstract;
use NeoCube\Router;

//--Configuração de um módulo da aplicação compartilhada
//--pelos runtimes FPM e Swoole. Por convenção tudo é deduzido de $module:
//--  {$module}\Router, {$module}\Error, src/{$module}/Views/ e src/{$module}/Views/Layouts/
//--mas qualquer item pode ser sobrescrito por argumento nomeado.
final class AppConfig {

    public readonly string $sourcePath;
    public readonly string $envFile;
    public readonly string $routerClass;
    public readonly string $errorClass;
    public readonly string $viewPath;
    public readonly string $layoutPath;

    public function __construct(
        public readonly string $basePath,
        public readonly string $module,
        public readonly string $publicDir = '/',
        ?string $sourcePath = null,
        ?string $envFile = null,
        ?string $routerClass = null,
        ?string $errorClass = null,
        ?string $viewPath = null,
        ?string $layoutPath = null,
        //--null = usa TIMEZONE do .env, se houver; senão mantém o date.timezone do php.ini.
        public readonly ?string $timezone = null,
        //--null = não mexe no display_errors do php.ini.
        public readonly ?bool $displayErrors = null,
    ) {
        $this->sourcePath  = rtrim($sourcePath ?? $basePath . '/src', '/');
        $this->envFile     = $envFile ?? $this->sourcePath . '/.env';
        $this->routerClass = $routerClass ?? $module . '\\Router';
        $this->errorClass  = $errorClass ?? $module . '\\Error';
        $this->viewPath    = $viewPath ?? "{$this->sourcePath}/{$module}/Views/";
        $this->layoutPath  = $layoutPath ?? "{$this->viewPath}Layouts/";

        //--Falha já no boot, e não na primeira requisição.
        if (!is_subclass_of($this->routerClass, Router::class) && $this->routerClass !== Router::class) {
            throw new InvalidArgumentException("Router class \"{$this->routerClass}\" must extend " . Router::class);
        }
        if (!is_subclass_of($this->errorClass, ErrorAbstract::class)) {
            throw new InvalidArgumentException("Error class \"{$this->errorClass}\" must extend " . ErrorAbstract::class);
        }
    }
}
