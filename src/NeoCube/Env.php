<?php

namespace NeoCube;

use Exception;
use InvalidArgumentException;
use RuntimeException;

class Env {

    protected static null|string $path = null;

    static public function load(string $file): void {
        if (static::$path !== null)
            throw new Exception('Env has ben loaded!');
        if (!file_exists($file))
            throw new InvalidArgumentException(sprintf('%s does not exist', $file));
        if (!is_readable($file))
            throw new RuntimeException(sprintf('%s file is not readable', $file));

        static::$path = dirname($file);

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {

            if (strpos(trim($line), '#') === 0) continue;

            list($name, $value) = explode('=', $line, 2);
            $name = self::processName($name);

            if (array_key_exists($name, $_SERVER) or array_key_exists($name, $_ENV)) continue;

            $value = self::processValue($value);
            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
        }
    }

    static public function getPath() {
        return static::$path;
    }


    //--Valor da variável: o do .env já convertido (true/false/null/número) ou o
    //--texto do ambiente (docker). null se ela não existe ou está vazia (null / '');
    //--false e 0 do .env são valores.
    static public function getValue(string $name): mixed {
        $name = self::processName($name);
        if (array_key_exists($name, $_ENV)) $value = $_ENV[$name];
        else if (($value = getenv($name)) === false) return null;
        return $value === '' ? null : $value;
    }

    //--getValue() com $default para variável ausente ou vazia. Com array:
    //--getValues(), ou $default se nenhuma estiver preenchida
    static public function get(array | string $value, mixed $default = ''): mixed {
        if (is_array($value)) return self::getValues($value) ?: $default;
        return self::getValue($value) ?? $default;
    }

    //--Só as variáveis preenchidas, mantendo as chaves do array informado
    //--ou reindexando se for lista
    static public function getValues(array $names): array {
        $values = [];
        foreach ($names as $key => $name) {
            $value = self::getValue($name);
            if ($value !== null) $values[$key] = $value;
        }
        return array_is_list($names) ? array_values($values) : $values;
    }

    static public function setValue(string $name, mixed $value) {
        $name = self::processName($name);
        $value = self::processValue($value);
        putenv("{$name}={$value}");
        $_ENV[$name] = $value;
    }

    static public function setValues(array $values) {
        foreach ($values as $name => $value)
            self::setValue($name, $value);
    }


    static private function processName(string $name) {
        return strtoupper(trim($name));
    }

    static private function processValue(string $value) {
        $value = trim($value);
        if (substr($value, 0, 2) == './')
            $value = static::$path . substr($value, 1);
        $processValue = match (true) {
            is_numeric($value) => (filter_var($value, FILTER_VALIDATE_INT) ?: (float) $value),

            in_array(strtolower($value), ['true', 'false']) => strtolower($value) === 'true',

            strtolower($value) == 'null' => null,

            default => $value,
        };
        return $processValue;
    }
}
