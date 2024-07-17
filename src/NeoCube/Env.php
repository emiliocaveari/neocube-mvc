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


    static public function get(array | string $value) {
        return match (true) {
            is_string($value) => self::getValue($value),
            is_array($value)  => self::getValues($value),
            default => null
        };
    }

    static public function getValue(string $name) {
        $name = self::processName($name);
        return $_ENV[$name] ?? getenv($name);
    }

    static public function getValues(array $names): array {
        $values = [];
        $isList = array_is_list($names);
        foreach ($names as $key => $name) {
            $value = static::getValue($name);
            if (!$value and $value !== false) continue;
            if ($isList) $values[] = $value;
            else $values[$key] = $value;
        }
        return $values;
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
