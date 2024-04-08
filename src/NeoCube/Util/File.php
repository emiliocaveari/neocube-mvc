<?php

namespace NeoCube\Util;

use InvalidArgumentException;
use RuntimeException;
use stdClass;

class File
{

    static protected $filename;
    static protected $content;
    static protected $path;

    static public function save(string $filename, string $content): bool
    {
        if (file_put_contents($filename, $content)) return true;
        else return false;
    }

    static public function read(string $filename): string
    {
        if (!file_exists($filename))
            throw new InvalidArgumentException(sprintf('%s does not exist', $filename));
        if (!is_readable($filename))
            throw new RuntimeException(sprintf('%s file is not readable', $filename));

        $content = file_get_contents($filename);
        return $content;
    }
    
    static public function readJson(string $filename, bool $associative = true): Array|stdClass 
    {
        $json = static::read($filename);
        $content = json_decode($json,$associative);
        if ( $content === null ) 
            throw new RuntimeException(sprintf('%s file not content JSON Data!', $filename));
        return $content;
    }

    static public function delete(string $filename): bool
    {
        if (!file_exists($filename))
            throw new InvalidArgumentException(sprintf('%s does not exist', $filename));
        
        if (unlink($filename)) return true;
        return false;
    }
}
