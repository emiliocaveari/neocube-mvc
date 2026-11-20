<?php

namespace NeoCube\Session;

use NeoCube\Env;
use NeoCube\Http\CookieOptions;
use NeoCube\Http\RequestContext;
use Swoole\Database\RedisConfig;
use Swoole\Database\RedisPool;
use Swoole\Table;

class SessionManager {

    private static ?Table $table = null;
    private static ?RedisPool $redisPool = null;

    const COOKIE_NAME   = 'NEOCUBE_SID';
    const TTL_SECONDS   = 4 * 3600;

    //--Padrões do tamanho da Swoole\Table (ajustáveis no .env por
    //--SESSION_TABLE_ROWS / SESSION_TABLE_ROW_BYTES). Cada sessão acaba ocupando
    //--a linha inteira em memória, então a linha deve ser pouco maior que a
    //--sessão real: 4096 x 8 KB = ~32 MB no pior caso (com 64 KB eram ~275 MB)
    const MAX_ROWS      = 4096;
    const MAX_ROW_BYTES = 8192;

    //--Inatividade a partir da qual uma sessão é descartada quando a tabela
    //--enche (ver TableSessionStore::persist), antes do TTL normal
    const IDLE_SECONDS_WHEN_FULL = 1800;

    //--Tamanho da coluna data, definido no createTable() (no master; os
    //--workers herdam): o Swoole corta sem erro a string maior que a coluna
    private static int $rowBytes = self::MAX_ROW_BYTES;

    //--Chamado uma única vez, no processo master, antes de $server->start() —
    //--Swoole\Table é memória compartilhada e precisa existir antes do fork
    //--dos workers pra ser herdada por todos eles.
    public static function createTable(): Table {
        self::$rowBytes = (int) Env::get('SESSION_TABLE_ROW_BYTES', self::MAX_ROW_BYTES);

        $table = new Table((int) Env::get('SESSION_TABLE_ROWS', self::MAX_ROWS));
        $table->column('data', Table::TYPE_STRING, self::$rowBytes);
        $table->column('last_access', Table::TYPE_INT);
        $table->create();
        return $table;
    }

    public static function useTable(Table $table): void {
        self::$table = $table;
    }

    public static function rowBytes(): int {
        return self::$rowBytes;
    }

    //--Ocupação da Swoole\Table (null fora do modo table). A capacidade real é
    //--~70% das linhas por causa das colisões de hash (medido no Swoole 6.2)
    public static function tableStats(): ?array {
        if (!self::$table) return null;
        return [
            'sessoes'    => self::$table->count(),
            'linhas'     => self::$table->getSize(),
            'capacidade' => (int) floor(self::$table->getSize() * 0.7),
        ];
    }

    //--Chamado uma vez por worker, dentro de onWorkerStart — diferente da
    //--Swoole\Table, o pool usa Swoole\Coroutine\Channel por baixo, que não
    //--sobrevive a fork, então só pode ser criado DEPOIS dele.
    public static function createRedisPool(): RedisPool {
        $config = (new RedisConfig())
            ->withHost(Env::get('REDIS_HOST', 'redis'))
            ->withPort((int) Env::get('REDIS_PORT', '6379'));
        return new RedisPool($config);
    }

    public static function useRedisPool(RedisPool $pool): void {
        self::$redisPool = $pool;
    }

    public static function isTableMode(): bool {
        return self::$table !== null;
    }

    public static function isRedisMode(): bool {
        return self::$redisPool !== null;
    }

    //--FPM com sessão sob demanda (AppConfig::$lazySession): session_start()
    //--só quando a sessão for usada, e não no início de toda requisição
    private static bool $nativeLazyStart = false;

    public static function useNativeLazyStart(bool $lazy): void {
        self::$nativeLazyStart = $lazy;
    }

    //--Atributos do cookie de sessão, resolvidos uma vez no boot
    private static array $cookieOptions = [];

    //--Chamado no boot do Kernel, depois de carregar o .env: uma aplicação por
    //--processo (no Swoole, roda no master e os workers herdam). Junta os atributos
    //--da aplicação (AppConfig::$sessionCookie) com os do .env, que têm prioridade.
    //--Sem nenhum dos dois o cookie sai como sempre saiu.
    public static function useCookieOptions(array $options): void {
        self::$cookieOptions = CookieOptions::normalize(array_merge($options, self::envCookieOptions()));
    }

    public static function cookieOptions(): array {
        return self::$cookieOptions;
    }

    //--O que depende do ambiente (secure, domain), ou um ajuste sem mudar código:
    //--  SESSION_COOKIE_HTTPONLY e SESSION_COOKIE_SECURE=0|1|true|false|on|off
    //--  SESSION_COOKIE_SAMESITE=Lax|Strict|None  SESSION_COOKIE_DOMAIN=...
    //--Lê com getValue(): Env::get() trataria "0" como vazio e o .env não
    //--conseguiria desligar o que o Bootstrap ligou. Valor inválido é ignorado
    //--(fica o do Bootstrap): um erro de digitação não pode desligar a proteção.
    private static function envCookieOptions(): array {
        $options = [];

        foreach (['httponly' => 'SESSION_COOKIE_HTTPONLY', 'secure' => 'SESSION_COOKIE_SECURE'] as $option => $name) {
            //--getValue() devolve false tanto para "ausente" quanto para "false" no .env
            $value = Env::getValue($name);
            if (($value === false and !array_key_exists($name, $_ENV)) or $value === null or $value === '') continue;

            $bool = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($bool !== null) $options[$option] = $bool;
        }

        $sameSite = CookieOptions::sameSite(Env::getValue('SESSION_COOKIE_SAMESITE'));
        if ($sameSite !== null) $options['samesite'] = $sameSite;

        if (($domain = (string) Env::getValue('SESSION_COOKIE_DOMAIN')) !== '')
            $options['domain'] = $domain;

        return $options;
    }

    //--Aplica cookieOptions() à sessão nativa do PHP (FPM). Chamar antes do session_start().
    public static function configureNativeCookie(): void {
        $options = self::cookieOptions();
        if (!$options or session_status() === PHP_SESSION_ACTIVE) return;
        session_set_cookie_params(array_merge(session_get_cookie_params(), $options));
    }

    public static function create(RequestContext $ctx): SessionStoreInterface {
        if (!self::isTableMode() && !self::isRedisMode()) {
            //--No Swoole a sessão nativa seria compartilhada entre as requisições
            //--do worker. Sem tabela/Redis só fica o módulo com lazySession (Api,
            //--autenticada por JWT), que não deve usar sessão
            if (defined('NEOCUBE_SWOOLE'))
                throw new \RuntimeException('Sessão indisponível neste módulo: com lazySession no Swoole a Swoole\Table não é criada (use JWT ou SESSION_DRIVER=redis)');

            if (self::$nativeLazyStart and session_status() === PHP_SESSION_NONE) self::startNativeSession();
            return new NativePhpSessionStore();
        }

        //--Id enviado pelo cliente só vale se tiver o formato gerado aqui e já
        //--existir no backend: um id inventado nunca vira sessão (fixação).
        $sessionId = $ctx->cookies[self::COOKIE_NAME] ?? null;
        if (!is_string($sessionId) or !preg_match('/^[a-f0-9]{32}$/', $sessionId)) $sessionId = null;

        if (self::isRedisMode()) {
            if (!$sessionId or !RedisSessionStore::exists(self::$redisPool, $sessionId)) $sessionId = bin2hex(random_bytes(16));
            $store = new RedisSessionStore(self::$redisPool, $sessionId, self::TTL_SECONDS);
        } else {
            if (!$sessionId or !self::$table->exist($sessionId)) $sessionId = bin2hex(random_bytes(16));
            $store = new TableSessionStore(self::$table, $sessionId);
        }

        self::sendCookie($ctx, $sessionId);
        return $store;
    }

    //--Troca o id da sessão mantendo os dados. Chamar ao mudar o nível de acesso
    //--(login, logout): quem conhecia o id anterior não herda a sessão autenticada.
    public static function regenerate(RequestContext $ctx): void {
        $sessionId = $ctx->sessionStore()->regenerate();
        if ($sessionId !== null) self::sendCookie($ctx, $sessionId);
    }

    private static function sendCookie(RequestContext $ctx, string $sessionId): void {
        $ctx->addCookie(self::COOKIE_NAME, $sessionId, time() + self::TTL_SECONDS, '/', false, self::cookieOptions());
    }

    //--lazySession no FPM: depois de a resposta sair, a sessão não pode mais ser
    //--aberta. Falha com erro claro em vez de perder os dados sem aviso (numa
    //--tarefa do Defer, o DeferredRunner loga a exceção).
    private static function startNativeSession(): void {
        if (headers_sent($file, $line))
            throw new \RuntimeException("Session used after output started at {$file}:{$line}");
        if (!session_start())
            throw new \RuntimeException('Could not start the native PHP session');
    }

    //--Roda periodicamente (worker 0) removendo sessões inativas há mais de
    //--TTL_SECONDS. Só se aplica ao modo Swoole\Table — no Redis a expiração
    //--é nativa (SETEX/EXPIRE), não precisa de varredura manual.
    //--$idleSeconds menor que o TTL: limpeza de emergência com a tabela cheia
    public static function gc(?int $idleSeconds = null): void {
        if (!self::$table) return;
        $expireBefore = time() - ($idleSeconds ?? self::TTL_SECONDS);

        //--Coleta antes de apagar: del() durante o foreach faz o iterador da
        //--Swoole\Table pular linhas (~13% das expiradas ficavam para trás)
        $expiradas = [];
        foreach (self::$table as $sessionId => $row) {
            if ($row['last_access'] < $expireBefore) $expiradas[] = $sessionId;
        }
        foreach ($expiradas as $sessionId) self::$table->del($sessionId);
    }
}
