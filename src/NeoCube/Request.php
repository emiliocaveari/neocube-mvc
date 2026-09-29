<?php

namespace NeoCube;

use NeoCube\Application\ApplicationContext;
use NeoCube\Application\ApplicationContextStorage;
use NeoCube\Http\ContextStorage;
use NeoCube\Validate;

class Request {

    static private function get_input_values(): array|string|null {
        $ctx = ContextStorage::current();
        $values = $ctx->rawBody ?: array();
        if ($values) {
            $contentType = $ctx->getHeader('Content-Type') ?: ($ctx->server['CONTENT_TYPE'] ?? '');
            if (stripos((string) $contentType, 'application/json') !== false) {
                $values = json_decode($values, true);
            }
        }
        return $values;
    }


    static protected function readRequestData(): void {
        $ctx = ContextStorage::current();
        $ctx->requestMethod = strtoupper($ctx->method);

        $data = match ($ctx->requestMethod) {
            'POST'         => $ctx->post ?: static::get_input_values(),
            'GET'          => $ctx->get,
            'DELETE', 'PUT' => static::get_input_values(),
            default        => [],
        };
        if (!is_array($data)) $data = [];

        foreach ($ctx->files as $key => $file) {
            $data[$key] = $file;
        }

        if (isset($data['_method'])) {
            $ctx->requestMethod = strtoupper(trim($data['_method']));
            unset($data['_method']);
        }

        $ctx->requestData = $data;
        $ctx->requestDataRead = true;
    }



    static public function request(): bool {
        $ctx = ContextStorage::current();
        if (!$ctx->requestDataRead) static::readRequestData();
        return (bool) $ctx->requestData;
    }

    static public function getData(bool|string|array $data = false, mixed $defaultEmpty = null): mixed {
        $ctx = ContextStorage::current();
        if (!$ctx->requestDataRead or $data === true) static::readRequestData();

        if (is_array($data)) {
            $arrData = [];
            foreach ($data as $k)
                $arrData[$k] = $ctx->requestData[$k] ?? $defaultEmpty;
            return $arrData;
        }

        if (is_string($data))
            return $ctx->requestData[$data] ?? $defaultEmpty;

        return $ctx->requestData;
    }

    static public function getHeader(string $header = '', bool $reload = false): mixed {
        $ctx = ContextStorage::current();
        return empty($header) ? $ctx->headers : $ctx->getHeader($header);
    }

    static public function getMethod(): string {
        $ctx = ContextStorage::current();
        if (!$ctx->requestDataRead) static::readRequestData();
        return $ctx->requestMethod;
    }
    static public function isMethod($method): bool {
        return (strtoupper(static::getMethod()) === strtoupper($method));
    }

    static public function validateData(array $arguments, $data = null): bool {
        if (is_null($data)) $data = static::getData();
        Validate::clear();
        return Validate::data($data, $arguments);
    }

    static public function validateErrors(): array {
        return Validate::getDataErrors();
    }


    static public function getToUrl(array $arr = []): string {
        if (static::isMethod('get')) {
            $data = static::getData();
            $get  = array_merge($data, $arr);
        } else {
            $get = $arr;
        }
        return '?' . http_build_query($get);
    }

    static public function isAjax(): bool {
        $header = ContextStorage::current()->getHeader('X-Requested-With');
        return ($header !== false and strtolower($header) === 'xmlhttprequest');
    }


    //--Executa um controller/action internamente (ex.: Select2Ajax), sem
    //--precisar de uma nova requisição HTTP. Roda num RequestContext clonado
    //--do atual — herda cookies/sessão (mesmo usuário autenticado), mas isola
    //--router, resposta em construção e erros de validação, para não vazar
    //--nada da sub-chamada para a requisição real.
    static public function httpController(string $url, ?array $data = null, ?string $method = null, ?array $headers = null) {
        $current = ContextStorage::current();
        $routerClass = get_class(Application::Router());

        $query = parse_url($url)['query'] ?? null;
        $queryParams = [];
        if ($query) parse_str($query, $queryParams);

        $subCtx = clone $current;
        $subCtx->responseStatus    = 200;
        $subCtx->responseHeaders   = [];
        $subCtx->responseCookies   = [];
        $subCtx->responseBody      = '';
        $subCtx->appFlags          = [];
        $subCtx->validationErrors  = [];
        $subCtx->requestData       = array_merge($data ?? [], $queryParams);
        $subCtx->requestDataRead   = true;

        if ($method and in_array(strtolower($method), ['get', 'post', 'put', 'delete'])) {
            $subCtx->requestMethod = strtoupper($method);
        }
        if ($headers) {
            $subCtx->headers = is_array($subCtx->headers) ? array_merge($subCtx->headers, $headers) : $headers;
        }

        //--router/error não moram mais no RequestContext (ver ApplicationContextStorage),
        //--então isolar a sub-chamada exige trocar as duas stores em conjunto.
        $currentAppCtx = ApplicationContextStorage::current();

        ContextStorage::set($subCtx);
        ApplicationContextStorage::set(new ApplicationContext());
        try {
            Application::setRouter(new $routerClass());
            Application::Router()->routing($url);
            $content = Application::Router()->getOut();
        } finally {
            //--Conexões de banco emprestadas do pool durante a sub-chamada precisam
            //--ficar rastreadas no contexto real, senão nunca voltam pro pool no
            //--fim da requisição de verdade (Connection::releaseAll roda só nele).
            $current->dbConnections   = array_merge($current->dbConnections, $subCtx->dbConnections);
            $current->dbPoolsBorrowed = array_merge($current->dbPoolsBorrowed, $subCtx->dbPoolsBorrowed);

            ContextStorage::set($current);
            ApplicationContextStorage::set($currentAppCtx);
        }

        return $content;
    }
}
