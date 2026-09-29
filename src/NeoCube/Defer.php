<?php

namespace NeoCube;

use NeoCube\Http\ContextStorage;

//--Registra uma tarefa para rodar só depois que a resposta já foi entregue ao
//--cliente. O disparo em si (coroutine solta no Swoole, fastcgi_finish_request
//--no FPM) é responsabilidade do Http\DeferredRunner, chamado pelo entrypoint
//--logo após emitir a resposta — aqui só se empilha a tarefa no contexto atual.
final class Defer {

    private function __construct() {}

    static public function run(callable $task): void {
        ContextStorage::current()->deferred[] = $task;
    }
}
