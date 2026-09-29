<?php

namespace NeoCube\Http;

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
        return $ctx;
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

    public function addCookie(string $name, string $value, int $expire, string $path = '/', bool $clear = false): void {
        $this->responseCookies[] = compact('name', 'value', 'expire', 'path', 'clear');
    }

    public function setBody(string $body): void {
        $this->responseBody = $body;
    }

    public function sessionStore(): SessionStoreInterface {
        return $this->sessionStoreInstance ??= SessionManager::create($this);
    }
}
