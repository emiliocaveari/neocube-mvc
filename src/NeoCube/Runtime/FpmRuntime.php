<?php

namespace NeoCube\Runtime;

use NeoCube\Http\ContextStorage;
use NeoCube\Http\DeferredRunner;
use NeoCube\Http\Kernel;
use NeoCube\Http\NativeEmitter;
use NeoCube\Http\RequestContext;
use NeoCube\Session\SessionManager;

//--Modelo "um processo por requisição" (PHP-FPM, Apache mod_php): boot,
//--atende a requisição atual a partir das superglobais e emite a resposta.
final class FpmRuntime {

    public function __construct(
        private Kernel $kernel
    ) {
    }

    public function run(): void {
        //--boot antes da sessão: o .env (atributos do cookie de sessão) precisa
        //--estar carregado. Nada é emitido aqui — a resposta só sai no fim, pelo emitter.
        $this->kernel->boot();

        //--Sessão nativa do PHP (Session\NativePhpSessionStore). Com lazySession,
        //--session_start() fica para o primeiro uso (SessionManager::create()).
        SessionManager::configureNativeCookie();
        if ($this->kernel->config()->lazySession) SessionManager::useNativeLazyStart(true);
        else session_start();

        $ctx = RequestContext::fromGlobals();
        ContextStorage::set($ctx);

        $this->kernel->handle($ctx);

        NativeEmitter::emit($ctx);
        DeferredRunner::flush($ctx);
    }
}
