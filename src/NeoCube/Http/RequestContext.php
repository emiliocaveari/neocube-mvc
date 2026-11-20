<?php

namespace NeoCube\Http;

use NeoCube\Env;
use NeoCube\Session\SessionManager;
use NeoCube\Session\SessionStoreInterface;

class RequestContext {

    //--Dados de entrada da requisição--//
    public string $method = 'GET';
    public array $get = [];
    public array $post = [];
    public array $files = [];
    public array $cookies = [];
    public array $headers = [];
    public array $server = [];
    public string $rawBody = '';

    //--Cache do NeoCube\Request--//
    public ?array $requestData = null;
    public ?string $requestMethod = null;
    public bool $requestDataRead = false;

    //--Resposta em construção (antes emitida direto via header()/echo)--//
    public int $responseStatus = 200;
    public array $responseHeaders = [];
    public array $responseCookies = [];
    public string $responseBody = '';

    //--Conexões de banco emprestadas de um pool (modo Swoole)--//
    public array $dbConnections = [];
    public array $dbPoolsBorrowed = [];

    //--Tarefas registradas via NeoCube\Defer, disparadas só depois que a
    //--resposta já foi entregue ao cliente (ver Http\DeferredRunner).
    public array $deferred = [];

    //--Sessão--//
    private ?SessionStoreInterface $sessionStoreInstance = null;

    //--Balde livre para flags/estado por requisição definidos pela aplicação
    //--(ex.: App\Request::$isPageOnly, que também era static antes desta refatoração).
    public array $appFlags = [];

    //--Erros de validação da requisição atual (antes era Validate::$_dataErros, static).
    public array $validationErrors = [];

    public static function fromGlobals(): static {
        $ctx = new static();
        $ctx->method  = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $ctx->get     = $_GET;
        $ctx->post    = $_POST;
        $ctx->files   = $_FILES;
        $ctx->cookies = $_COOKIE;
        $ctx->server  = $_SERVER;
        $ctx->rawBody = file_get_contents('php://input') ?: '';
        $ctx->headers = function_exists('getallheaders') ? (getallheaders() ?: []) : [];
        if (self::forceHttps()) $ctx->server['HTTPS'] = 'on';
        return $ctx;
    }

    //--NEOCUBE_FORCE_HTTPS=1: toda requisição é tratada como HTTPS (URLs absolutas
    //--do Router, como createLink(), saem com https://), sem depender do
    //--X-Forwarded-Proto. Para quando o TLS termina antes de um proxy que
    //--repassa o esquema errado (ex.: DMZ HTTPS -> nginx interno HTTP)
    private static function forceHttps(): bool {
        return filter_var(Env::getValue('NEOCUBE_FORCE_HTTPS'), FILTER_VALIDATE_BOOLEAN);
    }

    public static function fromSwooleRequest(\Swoole\Http\Request $request): static {
        $ctx = new static();

        $swServer = $request->server ?? [];

        $ctx->method  = strtoupper($swServer['request_method'] ?? 'GET');
        $ctx->get     = $request->get ?? [];
        $ctx->post    = $request->post ?? [];
        $ctx->files   = $request->files ?? [];
        $ctx->cookies = $request->cookie ?? [];
        $ctx->rawBody = $request->rawContent() ?: '';
        $ctx->headers = $request->header ?? [];

        $server = [];
        foreach ($ctx->headers as $name => $value) {
            $server['HTTP_' . strtoupper(str_replace('-', '_', $name))] = $value;
        }
        $server['REQUEST_METHOD']  = $ctx->method;
        $server['REQUEST_URI']     = $swServer['request_uri'] ?? '/';
        $server['QUERY_STRING']    = $swServer['query_string'] ?? '';
        $server['REMOTE_ADDR']     = $swServer['remote_addr'] ?? '';
        $server['CONTENT_TYPE']    = $ctx->headers['content-type'] ?? null;
        $server['CONTENT_LENGTH']  = (int) ($ctx->headers['content-length'] ?? 0);
        if (isset($ctx->headers['host'])) $server['HTTP_HOST'] = $ctx->headers['host'];
        if (isset($swServer['https']) && $swServer['https']) $server['HTTPS'] = 'on';
        //--atrás de proxy/túnel que termina o TLS (Cloudflare Tunnel, ngrok, nginx) o
        //--Swoole recebe HTTP puro: o esquema original vem no X-Forwarded-Proto
        //--(pode trazer lista "https,http" — vale o primeiro, do cliente)
        $proto = strtolower(trim(explode(',', (string) ($ctx->headers['x-forwarded-proto'] ?? ''))[0]));
        if ($proto === 'https' or self::forceHttps()) $server['HTTPS'] = 'on';

        $ctx->server = $server;

        return $ctx;
    }

    public function getHeader(string $name): string|false {
        $name = strtolower($name);
        foreach ($this->headers as $k => $v) {
            if (strtolower($k) === $name) return $v;
        }
        return false;
    }

    public function setStatus(int $status): void {
        $this->responseStatus = $status;
    }

    public function addResponseHeader(string $header): void {
        $this->responseHeaders[] = $header;
    }

    //--$options (opcional): 'httponly' => bool, 'secure' => bool,
    //--'samesite' => 'Lax'|'Strict'|'None', 'domain' => string. Sem $options o
    //--cookie sai exatamente como antes (sem esses atributos).
    //--O mesmo cookie (nome + path) definido de novo substitui o anterior.
    public function addCookie(string $name, string $value, int $expire, string $path = '/', bool $clear = false, array $options = []): void {
        $options = CookieOptions::normalize($options);

        $this->responseCookies = array_values(array_filter(
            $this->responseCookies,
            fn(array $cookie) => $cookie['name'] !== $name or $cookie['path'] !== $path
        ));
        $this->responseCookies[] = compact('name', 'value', 'expire', 'path', 'clear', 'options');
    }

    //--A sessão foi usada nesta requisição? (sem criá-la)
    public function hasSessionStore(): bool {
        return $this->sessionStoreInstance !== null;
    }

    public function setBody(string $body): void {
        $this->responseBody = $body;
    }

    public function sessionStore(): SessionStoreInterface {
        return $this->sessionStoreInstance ??= SessionManager::create($this);
    }
}
