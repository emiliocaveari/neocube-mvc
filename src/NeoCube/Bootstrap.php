<?php

namespace NeoCube;

use NeoCube\Bootstrap\AppConfig;
use NeoCube\Http\Kernel;
use NeoCube\Runtime\FpmRuntime;
use NeoCube\Runtime\SwooleRuntime;

//--Ponto de entrada de um módulo NeoCube, igual para FPM e Swoole:
//--Os argumentos nomeados extras de app() vão direto para Bootstrap\AppConfig.
final class Bootstrap {

    private array $beforeRequest = [];
    private array $onWorkerStart = [];

    private function __construct(
        private AppConfig $config
    ) {
    }

    public static function app(string $basePath, string $module, mixed ...$options): self {
        return new self(new AppConfig($basePath, $module, ...$options));
    }

    public static function fromConfig(AppConfig $config): self {
        return new self($config);
    }

    //--Roda em toda requisição, com o Error do módulo já ativo e antes do roteamento.
    //--fn(Http\RequestContext $ctx): void
    public function beforeRequest(callable $callback): self {
        $this->beforeRequest[] = $callback;
        return $this;
    }

    //--Só Swoole: roda no boot de cada worker, depois dos pools de sessão/cache.
    //--fn(Swoole\Http\Server $server, int $workerId): void
    public function onWorkerStart(callable $callback): self {
        $this->onWorkerStart[] = $callback;
        return $this;
    }

    public function kernel(): Kernel {
        return new Kernel($this->config, $this->beforeRequest);
    }

    public function runFpm(): void {
        (new FpmRuntime($this->kernel()))->run();
    }

    //--$settings sobrescreve as opções padrão do Swoole\Http\Server (ver SwooleRuntime).
    public function runSwoole(int $port = 9501, string $host = '0.0.0.0', array $settings = []): void {
        (new SwooleRuntime($this->kernel(), $this->onWorkerStart))->run($host, $port, $settings);
    }
}
