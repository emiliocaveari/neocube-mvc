<?php

namespace NeoCube\Http;

class SwooleEmitter {

    public static function emit(\Swoole\Http\Response $response, RequestContext $ctx): void {
        $response->status($ctx->responseStatus);

        foreach ($ctx->responseHeaders as $header) {
            [$name, $value] = array_pad(explode(':', $header, 2), 2, '');
            $response->header(trim($name), trim($value));
        }

        foreach ($ctx->responseCookies as $cookie) {
            //--sem options, os padrões abaixo são os mesmos do Swoole: sai igual a antes
            $o = $cookie['options'];
            $response->cookie(
                $cookie['name'],
                $cookie['clear'] ? '' : $cookie['value'],
                $cookie['clear'] ? time() - 3600 : $cookie['expire'],
                $cookie['path'],
                (string) ($o['domain'] ?? ''),
                (bool) ($o['secure'] ?? false),
                (bool) ($o['httponly'] ?? false),
                (string) ($o['samesite'] ?? ''),
            );
        }

        $response->end($ctx->responseBody);
    }
}
