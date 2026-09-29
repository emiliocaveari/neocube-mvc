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
            if ($cookie['clear']) {
                $response->cookie($cookie['name'], '', time() - 3600, $cookie['path']);
            } else {
                $response->cookie($cookie['name'], $cookie['value'], $cookie['expire'], $cookie['path']);
            }
        }

        $response->end($ctx->responseBody);
    }
}
