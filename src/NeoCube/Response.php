<?php

namespace NeoCube;


class Response {

    const STATUS = array(
        200 => '200 OK',
        400 => '400 Bad Request',
        422 => 'Unprocessable Entity',
        500 => '500 Internal Server Error'
    );

    static public function getContentType(string $filename): string {
        $mime_types = array(
            'txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'php' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',

            // images
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',

            // archives
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',

            // audio/video
            'mp3' => 'audio/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',

            // adobe
            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',

            // ms office
            'doc' => 'application/msword',
            'rtf' => 'application/rtf',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',

            // open office
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        );
        $ext = explode('.', $filename);
        $ext = array_pop($ext);
        $ext = strtolower($ext);
        if (array_key_exists($ext, $mime_types)) {
            return $mime_types[$ext];
        } else if ($mimetype = mime_content_type($filename)) {
            return $mimetype;
        } else {
            return 'application/octet-stream';
        }
    }

    static public function json(mixed $objJson, int $code = 200, bool $clean = false) : false {
        if ($clean) ob_clean();
        header('Content-Type: application/json');
        http_response_code($code);
        header('status: ' . $code);
        echo json_encode($objJson);
        return false;
    }

    static public function text(string $text, int $code = 200, bool $clean = false): false {
        if ($clean) ob_clean();
        header('Content-Type: text/plain');
        http_response_code($code);
        header("status: {$code}");
        echo $text;
        return false;
    }


    static public function html(string $text, bool $clean = false): false {
        if ($clean) ob_clean();
        header('Content-Type: text/html');
        http_response_code(200);
        header('status: 200');
        echo $text;
        return false;
    }

    static public function file($file, $contentType = null, int $code = 200, $clean = false): false {
        if (is_null($contentType)) $contentType = self::getContentType($file);
        if ($clean) ob_clean();
        header("Content-Type: {$contentType}");
        http_response_code($code);
        header("status: {$code}");
        readfile($file);
        return false;
    }
}
