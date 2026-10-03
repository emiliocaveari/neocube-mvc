<?php

namespace NeoCube\Http;

use NeoCube\Application;
use NeoCube\Bootstrap\AppConfig;
use NeoCube\Env;
use NeoCube\Error\ErrorType;

//--Núcleo comum aos runtimes FPM e Swoole: preparação do ambiente (boot) e
//--o tratamento de uma requisição já convertida em RequestContext (handle).
//--Não sabe nada de como a requisição chega nem de como a resposta sai —
//--isso é responsabilidade de Runtime\FpmRuntime / Runtime\SwooleRuntime.
final class Kernel {

    //--@param callable[] $beforeRequest  fn(RequestContext $ctx): void
    public function __construct(
        private AppConfig $config,
        private array $beforeRequest = []
    ) {
    }

    public function config(): AppConfig {
        return $this->config;
    }

    //--Uma vez por processo: no FPM a cada requisição (processo novo), no
    //--Swoole no processo master, antes do fork — os workers herdam o estado.
    public function boot(): void {
        //--Env::load() lança exceção se chamado duas vezes; e um .env ausente
        //--não impede o boot (as variáveis podem vir só do ambiente).
        if (Env::getPath() === null && is_file($this->config->envFile)) {
            Env::load($this->config->envFile);
        }

        Env::setValues([
            "LAYOUT_PATH" => $this->config->layoutPath,
            "VIEW_PATH"   => $this->config->viewPath,
            "PUBLIC_DIR"  => $this->config->publicDir,
            "PATH_SOURCE" => $this->config->sourcePath,
        ]);

        if ($timezone = $this->config->timezone ?: Env::get('TIMEZONE')) {
            date_default_timezone_set($timezone);
        }

        if ($this->config->displayErrors !== null) {
            ini_set('display_errors', $this->config->displayErrors ? '1' : '0');
        }
    }

    public function handle(RequestContext $ctx): void {
        try {
            $Error = new ($this->config->errorClass)();
            Application::setErrorReporting($Error);

            foreach ($this->beforeRequest as $callback) $callback($ctx);

            $Router = new ($this->config->routerClass)();
            $Router->setPublicDir($this->config->publicDir);

            Application::startModules($Router);
        } catch (RequestTerminated) {
            //--Resposta já foi montada no contexto (redirect, página de erro, etc).
        } catch (\Throwable $e) {
            try {
                //--SHUTDOWN é o único tipo que App\Error::onError() trata como fatal
                //--(renderiza página de erro e encerra); os demais só logam e seguem.
                Application::ErrorReporting()->dispatch($e, ErrorType::SHUTDOWN);
            } catch (RequestTerminated) {
                //--Resposta já foi montada no contexto pelo próprio error handler.
            }
        }
    }
}
