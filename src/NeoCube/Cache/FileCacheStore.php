<?php

namespace NeoCube\Cache;

use NeoCube\Application;

//--Cache em arquivos no disco local. A expiração vai junto do conteúdo
//--serializado, já que o sistema de arquivos não tem TTL nativo.
class FileCacheStore implements CacheStoreInterface {

    public function __construct(
        protected string $folder
    ) {
    }

    protected function generateFileLocation(string $key): string {
        return $this->folder . DIRECTORY_SEPARATOR . sha1($key) . '.tmp';
    }

    public function get(string $key): mixed {
        $filename = $this->generateFileLocation($key);
        if (!is_file($filename)) return false;

        $raw = @file_get_contents($filename);
        if ($raw === false) return false;

        $cache = @unserialize($raw);
        if (!is_array($cache) || !isset($cache['expires']) || !array_key_exists('content', $cache)) {
            @unlink($filename);
            return false;
        }
        if ($cache['expires'] <= time()) {
            //--@: outra requisição pode ter removido o arquivo nesse meio tempo.
            @unlink($filename);
            return false;
        }
        return $cache['content'];
    }

    public function set(string $key, mixed $content, int $ttl): bool {
        if (!is_dir($this->folder) || !is_writable($this->folder)) {
            Application::ErrorReporting()->dispatch([
                'message' => 'Unable to access the cache folder "' . $this->folder . '"',
                'file'    => __FILE__,
                'line'    => __LINE__,
            ]);
            return false;
        }

        $filename = $this->generateFileLocation($key);
        $data = serialize([
            'expires' => time() + $ttl,
            'content' => $content
        ]);

        //--Grava num arquivo temporário e troca via rename() (atômico no mesmo
        //--filesystem): uma leitura concorrente nunca vê o arquivo pela metade.
        $tmp = $filename . '.' . bin2hex(random_bytes(4));
        try {
            if (file_put_contents($tmp, $data, LOCK_EX) === false) return false;
            if (!rename($tmp, $filename)) {
                @unlink($tmp);
                return false;
            }
            return true;
        } catch (\Throwable $th) {
            @unlink($tmp);
            Application::ErrorReporting()->dispatch($th);
            return false;
        }
    }

    public function delete(string $key): bool {
        $filename = $this->generateFileLocation($key);
        if (is_file($filename)) {
            return @unlink($filename) || !is_file($filename);
        }
        return true;
    }
}
