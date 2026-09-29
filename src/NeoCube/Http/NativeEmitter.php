<?php

namespace NeoCube\Http;

class NativeEmitter {

    public static function emit(RequestContext $ctx): void {
        foreach ($ctx->responseHeaders as $header) header($header);
        header('status: ' . $ctx->responseStatus);
        http_response_code($ctx->responseStatus);

        foreach ($ctx->responseCookies as $cookie) {
            if ($cookie['clear']) setcookie($cookie['name'], '', time() - 3600, $cookie['path']);
            else setcookie($cookie['name'], $cookie['value'], $cookie['expire'], $cookie['path']);
        }

        echo $ctx->responseBody;
    }
}
