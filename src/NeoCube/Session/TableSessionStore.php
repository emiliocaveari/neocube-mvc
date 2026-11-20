<?php

namespace NeoCube\Session;

use Swoole\Table;

//--Usada no modo Swoole: sessão guardada em memória compartilhada entre os
//--workers do mesmo servidor (Swoole\Table), já que session_start() nativo
//--(baseado em lock de arquivo) não se comporta bem em processo persistente.
class TableSessionStore implements SessionStoreInterface {

    use TracksSessionChanges;

    //--regenerate(): a linha nova recebe os dados inteiros, não só as alterações
    private bool $replaceAll = false;

    //--Com a tabela cheia, a limpeza de emergência e o aviso no log rodam no
    //--máximo uma vez por intervalo: numa rajada (bot, health check) cada
    //--requisição varreria a tabela inteira e gravaria uma linha de log.
    //--Estáticos = por worker (processos não compartilham)
    const INTERVALO_TABELA_CHEIA = 60;
    private static int $ultimaLimpeza = 0;
    private static int $ultimoAviso = 0;
    private static int $falhasDesdeAviso = 0;

    public function __construct(
        private Table $table,
        private string $sessionId
    ) {
        $this->data = $this->decode($table->get($sessionId)['data'] ?? null);
    }

    public function regenerate(): ?string {
        $this->table->del($this->sessionId);
        $this->sessionId = $this->newSessionId();
        $this->replaceAll = true;
        return $this->sessionId;
    }

    public function persist(): void {
        $current = $this->table->get($this->sessionId)['data'] ?? '{}';

        $data = match (true) {
            $this->replaceAll => json_encode($this->data),
            (bool) $this->changes => json_encode($this->applyChanges($this->decode($current))),
            default => $current,
        };

        //--O Swoole cortaria a string sem erro e o JSON truncado perderia a
        //--sessão inteira: mantém o dado anterior e registra para ajustar o tamanho
        $limite = SessionManager::rowBytes();
        if (strlen($data) > $limite) {
            error_log(sprintf('[NeoCube\Session] sessão com %d bytes excede SESSION_TABLE_ROW_BYTES=%d; alteração descartada', strlen($data), $limite));
            $data = $current;
        } else if (strlen($data) > $limite * 0.8) {
            error_log(sprintf('[NeoCube\Session] sessão com %d bytes, perto do limite SESSION_TABLE_ROW_BYTES=%d', strlen($data), $limite));
        }

        //--Sempre atualiza o last_access (usado pelo GC), mesmo sem mudança de dado.
        $row = ['data' => $data, 'last_access' => time()];
        if ($this->gravar($row)) return;

        //--Tabela cheia (ex.: acessos sem cookie criando sessões anônimas): descarta
        //--as sessões ociosas, sem afetar quem está usando, e tenta de novo
        $agora = time();
        if ($agora - self::$ultimaLimpeza >= self::INTERVALO_TABELA_CHEIA) {
            self::$ultimaLimpeza = $agora;
            SessionManager::gc(SessionManager::IDLE_SECONDS_WHEN_FULL);
            if ($this->gravar($row)) return;
        }

        self::$falhasDesdeAviso++;
        if ($agora - self::$ultimoAviso >= self::INTERVALO_TABELA_CHEIA) {
            error_log(sprintf('[NeoCube\Session] Swoole\Table de sessão cheia: %d sessão(ões) não gravada(s) neste worker desde o último aviso; aumente SESSION_TABLE_ROWS', self::$falhasDesdeAviso));
            self::$ultimoAviso = $agora;
            self::$falhasDesdeAviso = 0;
        }
    }

    //--Com a tabela cheia o set() emite um warning que o handler de erro da
    //--aplicação transforma em erro 500 (e ignora o @): silencia só aqui. Não há
    //--troca de coroutine durante o set(), então o handler não vaza para outra requisição
    private function gravar(array $row): bool {
        set_error_handler(fn() => true);
        try {
            return $this->table->set($this->sessionId, $row);
        } finally {
            restore_error_handler();
        }
    }
}
