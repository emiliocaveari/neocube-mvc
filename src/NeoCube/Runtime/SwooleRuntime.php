<?php

namespace NeoCube\Runtime;

use NeoCube\Cache\CacheManager;
use NeoCube\Db\Connection;
use NeoCube\Env;
use NeoCube\Http\ContextStorage;
use NeoCube\Http\DeferredRunner;
use NeoCube\Http\Kernel;
use NeoCube\Http\RequestContext;
use NeoCube\Http\SwooleEmitter;
use NeoCube\Session\SessionManager;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;

//--Servidor HTTP do Swoole: workers de vida longa, uma coroutine por requisição.
//--A ordem importa por causa do fork:
//--  master (antes do start): Env, constantes, Swoole\Table — herdados pelos workers;
//--  workerStart: tudo que usa Coroutine\Channel (pools Redis), que não sobrevive a fork;
//--  request: fluxo da requisição, isolado por coroutine via ContextStorage.
final class SwooleRuntime {

    //--@param callable[] $onWorkerStart  fn(Server $server, int $workerId): void
    public function __construct(
        private Kernel $kernel,
        private array $onWorkerStart = []
    ) {
    }

    public function run(string $host = '0.0.0.0', int $port = 9501, array $settings = []): void {
        if (PHP_SAPI !== 'cli' || !extension_loaded('swoole')) {
            throw new \RuntimeException('SwooleRuntime requires the CLI SAPI with the swoole extension.');
        }

        //--Sinaliza para o núcleo do NeoCube que está rodando sob o servidor HTTP do
        //--Swoole (worker de vida longa, request tratado por coroutine) em vez do
        //--modelo "processo novo por request" do FPM.
        if (!defined('NEOCUBE_SWOOLE')) define('NEOCUBE_SWOOLE', true);
        if (!defined('NEOCUBE_HTTP_CONTEXT')) define('NEOCUBE_HTTP_CONTEXT', true);

        //--No master: workers (inclusive os recriados por reload/SIGUSR1) herdam
        //--o Env já carregado — por isso o workerStart não chama boot() de novo.
        $this->kernel->boot();

        //--Lidos depois do Env::load(): valem tanto do ambiente do container quanto do .env.
        $sessionDriver = Env::get('SESSION_DRIVER', 'table');
        $cacheDriver   = Env::get('CACHE_DRIVER', 'file');

        if ($sessionDriver === 'table') {
            //--Swoole\Table precisa ser criada ANTES do fork dos workers para
            //--ficar em memória compartilhada entre todos eles.
            SessionManager::useTable(SessionManager::createTable());
        }

        $server = new Server($host, $port);
        $server->set(array_replace($this->defaultSettings(), $settings));

        $server->on('start', function () use ($host, $port) {
            echo "Swoole HTTP server started at http://{$host}:{$port}\n";
        });

        $server->on('workerStart', function (Server $server, int $workerId) use ($sessionDriver, $cacheDriver) {
            //--Libera I/O bloqueante (PDO, curl, file_get_contents, etc) para não travar
            //--o worker inteiro enquanto uma coroutine espera por rede/disco.
            \Swoole\Runtime::enableCoroutine(SWOOLE_HOOK_ALL);

            //--Pools usam Swoole\Coroutine\Channel, que não sobrevive a fork:
            //--um por worker, criado aqui.
            if ($sessionDriver === 'redis') {
                SessionManager::useRedisPool(SessionManager::createRedisPool());
            }
            if ($cacheDriver === 'redis') {
                CacheManager::useRedisPool(CacheManager::createRedisPool());
            }

            //--register_shutdown_function só dispara quando o WORKER termina (não a cada
            //--requisição), então é registrado uma única vez aqui, só como rede de
            //--segurança para crash do worker inteiro.
            register_shutdown_function(['NeoCube\Error\Controller', 'shutdown']);

            if ($workerId === 0 && $sessionDriver === 'table') {
                \Swoole\Timer::tick(60_000, fn() => SessionManager::gc());
            }

            foreach ($this->onWorkerStart as $callback) $callback($server, $workerId);
        });

        $server->on('request', function (Request $request, Response $response) {
            $ctx = RequestContext::fromSwooleRequest($request);
            ContextStorage::set($ctx);

            //--Xdebug não dispara sozinho por requisição sob o Swoole (não há RINIT
            //--por request); xdebug_break() força a conexão/pausa aqui manualmente.
            if (function_exists('xdebug_break') && Env::get('XDEBUG_MODE', 'off') !== 'off') xdebug_break();

            try {
                $this->kernel->handle($ctx);
            } finally {
                $ctx->sessionStore()->persist();
                Connection::releaseAll($ctx);

                SwooleEmitter::emit($response, $ctx);

                //--Depois do emit: o cliente já recebeu a resposta.
                DeferredRunner::flush($ctx);

                ContextStorage::clear();
            }
        });

        $server->start();
    }

    private function defaultSettings(): array {
        return [
            'worker_num'       => (int) Env::get('SWOOLE_WORKER_NUM', (string) swoole_cpu_num()),
            'enable_coroutine' => true,

            //--Teto do corpo da requisição (padrão do Swoole é só 2 MB). Alinhado com
            //--o post_max_size/client_max_body_size do stack nginx+FPM (108M).
            'package_max_length' => 108 * 1024 * 1024,

            //--Serve arquivos estáticos de /public direto pelo Swoole (equivalente ao
            //--que o nginx faz no FPM) sem passar pelo onRequest/bootstrap do NeoCube.
            'document_root'            => $this->kernel->config()->basePath . '/public',
            'enable_static_handler'    => true,
            'static_handler_locations' => ['/assets'],
        ];
    }
}
