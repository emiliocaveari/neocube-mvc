<?php

namespace NeoCube;


class Response {


    protected function __construct(
        private ?string $html = null,
        private mixed   $body = null,
        private ?string $file = null,
        private int     $code = 200,
        private array   $header = [],
        private bool    $clean = true,
    ) {
    }

    public function getBody() {
        return $this->body;
    }
    public function getCode() {
        return $this->code;
    }
    public function getHeader() {
        return $this->header;
    }
    public function getFile() {
        return $this->file;
    }


    final public function execute() {
        if ($this->clean and ob_get_length()) ob_clean();
        foreach ($this->header as $header)
            header($header);
        header('status: ' . $this->code);
        http_response_code($this->code);
        if ($this->html)
            echo $this->html;
        else if ($this->body)
            echo json_encode($this->body);
        else if ($this->file)
            readfile($this->file);
    }

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

            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',

            'mp3' => 'audio/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',

            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',

            'doc' => 'application/msword',
            'rtf' => 'application/rtf',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',

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

    static public function json(mixed $body, int $code = 200, bool $clean = true): static {
        return new static(
            body: $body,
            code: $code,
            clean: $clean,
            header: ['Content-Type: application/json']
        );
    }

    static public function text(string $text, int $code = 200, bool $clean = true): static {
        return new static(
            html: $text,
            code: $code,
            clean: $clean,
            header: ['Content-Type: text/plain']
        );
    }

    static public function html(string $html, bool $clean = true): static {
        return new static(
            html: $html,
            clean: $clean,
            header: ['Content-Type: text/html']
        );
    }

    static public function file(string $file, ?string $contentType = null, $clean = true): static {
        if (is_null($contentType)) $contentType = self::getContentType($file);
        return new static(
            file: $file,
            clean: $clean,
            header: ["Content-Type: {$contentType}"]
        );
    }
}
