<?php

namespace NeoCube\Http;

/**
 * Sinaliza que a resposta já foi montada no RequestContext e o processamento
 * da requisição deve parar aqui — substitui os antigos exit()/header()+exit()
 * espalhados no núcleo, que sob Swoole matariam o worker inteiro em vez de
 * só encerrar a requisição atual.
 */
class RequestTerminated extends \RuntimeException {
}
