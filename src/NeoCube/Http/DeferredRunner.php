<?php

namespace NeoCube\Http;

class DeferredRunner {

    //--Chamado pelo entrypoint logo após emitir a resposta (SwooleEmitter ou
    //--NativeEmitter), nunca antes. Erros de tarefa deferida só são logados —
    //--nada no fluxo normal da requisição está esperando por elas.
    static public function flush(RequestContext $ctx): void {
        if (!$ctx->deferred) return;

        $tasks = $ctx->deferred;
        $ctx->deferred = [];

        if (static::isSwoole()) {
            //--Cada tarefa vira uma coroutine independente: a resposta já foi
            //--escrita no socket, então o cliente não espera por elas, e o
            //--worker segue livre para o próximo request enquanto rodam.
            foreach ($tasks as $task) {
                \Swoole\Coroutine::create(static function () use ($task) {
                    static::runSafely($task);
                });
            }
            return;
        }

        //--Sem coroutine: libera o cliente (se o SAPI suportar) e roda em
        //--sequência, no mesmo processo, sem paralelismo.
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } elseif (function_exists('litespeed_finish_request')) {
            litespeed_finish_request();
        }

        foreach ($tasks as $task) {
            static::runSafely($task);
        }
    }

    static private function runSafely(callable $task): void {
        try {
            $task();
        } catch (\Throwable $e) {
            error_log('[NeoCube\Defer] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        }
    }

    static private function isSwoole(): bool {
        return defined('NEOCUBE_SWOOLE')
            && class_exists(\Swoole\Coroutine::class, false)
            && \Swoole\Coroutine::getCid() > 0;
    }
}
