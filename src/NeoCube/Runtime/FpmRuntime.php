<?php

namespace NeoCube\Runtime;

use NeoCube\Http\ContextStorage;
use NeoCube\Http\DeferredRunner;
use NeoCube\Http\Kernel;
use NeoCube\Http\NativeEmitter;
use NeoCube\Http\RequestContext;

//--Modelo "um processo por requisição" (PHP-FPM, Apache mod_php): boot,
//--atende a requisição atual a partir das superglobais e emite a resposta.
final class FpmRuntime {

    public function __construct(
        private Kernel $kernel
    ) {
    }

    public function run(): void {
        //--Sessão nativa do PHP (Session\NativePhpSessionStore). Seguro chamar
        //--antes de qualquer coisa: a resposta só é emitida no fim, pelo emitter.
        session_start();

        $this->kernel->boot();

        $ctx = RequestContext::fromGlobals();
        ContextStorage::set($ctx);

        $this->kernel->handle($ctx);

        NativeEmitter::emit($ctx);
        DeferredRunner::flush($ctx);
    }
}
