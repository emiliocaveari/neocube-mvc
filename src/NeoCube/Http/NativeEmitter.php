<?php

namespace NeoCube\Http;

class NativeEmitter {

    public static function emit(RequestContext $ctx): void {
        foreach ($ctx->responseHeaders as $header) header($header);
        header('status: ' . $ctx->responseStatus);
        http_response_code($ctx->responseStatus);

        foreach ($ctx->responseCookies as $cookie) {
            $params = [
                'expires' => $cookie['clear'] ? time() - 3600 : $cookie['expire'],
                'path'    => $cookie['path'],
            ] + $cookie['options'];

            setcookie($cookie['name'], $cookie['clear'] ? '' : $cookie['value'], $params);
        }

        echo $ctx->responseBody;
    }
}
