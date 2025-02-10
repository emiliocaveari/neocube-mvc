<?php

namespace NeoCube;

use NeoCube\Helper\Date;

class Validate {

    static array $_dataErros = [];


    static function getDataErrors(): array {
        return static::$_dataErros;
    }

    static public function data(array $data, array $arguments): bool {
        static::$_dataErros = [];
        foreach ($arguments as $key => $argument) {
            if (!isset($data[$key])) $data[$key] = '';
            if (
                !is_array($data[$key]) or
                isset($argument['multiple']) or
                isset($argument['file'])
            ) {
                $validateValue = self::value($data[$key], $argument);
                if ($validateValue !== true) static::$_dataErros[$key] = $validateValue;
            } else {
                foreach ($data[$key] as $k => $v) {
                    $validateValue = self::data([$k => $v], [$k => $argument]);
                    if ($validateValue !== true) static::$_dataErros[$key] = $validateValue;
                }
            }
        }
        return (count(static::$_dataErros)) ? false : true;
    }


    static public function value(mixed $value, array $argument): array|bool {

        $errors = [];

        if (is_array($value)) {

            $required = isset($argument['required']) ? $argument['required'] : false;

            if ($required and count($value) <= 0)
                $errors['required'] = 'invalid';

            if (isset($argument['max']) and count($value) > $argument['max'])
                $errors['max'] = $argument['max'];

            if (isset($argument['min']) and count($value) < $argument['min'])
                $errors['min'] = $argument['min'];

            if (isset($argument['file'])) {
                if ($argument['file'] === true and ($value['size'] == 0 or !empty($value['error']))) {
                    $errors['file'] = "File inválid!";
                }
            }
        } else {

            $strlen =  mb_strlen("{$value}");
            $required = isset($argument['required']) ? $argument['required'] : (bool) $strlen;

            if ($required and $strlen <= 0)
                $errors['required'] = 'invalid';

            $in = $argument['in_array'] ?? $argument['in'] ?? [];
            if ($required and $in)
                if (!in_array($value, $in))
                    $errors['in_array'] = 'Not content in list';


            if (isset($argument['maxlength']) and $strlen > $argument['maxlength'])
                $errors['maxlength'] = $argument['maxlength'];

            if ($required and isset($argument['minlength']) and $strlen < $argument['minlength'])
                $errors['minlength'] = $argument['minlength'];

            if (isset($argument['max']) and intval($value) > $argument['max'])
                $errors['max'] = $argument['max'];

            if (isset($argument['min']) and intval($value) < $argument['min'])
                $errors['min'] = $argument['min'];

            if (isset($argument['captcha']) and $argument['captcha'] != $value) {
                $errors['captcha'] = 'invalid';
            }

            if (isset($argument['dateformat']) and $strlen > 0) {
                if (!preg_match('/^[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}$/', $value)) {
                    $errors['dateformat'] = 'intalid';
                }
            }

            if (isset($argument['date']) and $strlen > 0 and !Date::dateFormat($value)) {
                $errors['date'] = 'intalid';
            }
            if (isset($argument['time']) and $strlen > 0 and !Date::timeFormat($value)) {
                $errors['time'] = 'intalid';
            }
            if (isset($argument['datetime']) and $strlen > 0 and !Date::dateTimeFormat($value)) {
                $errors['datetime'] = 'intalid';
            }

            if ($required and isset($argument['numeric'])) {
                if ($argument['numeric'] === true) $argument['numeric'] = 'numeric';
                switch ($argument['numeric']) {
                    case 'int':
                    case 'integer':
                        if (!is_int($value)) $errors['numeric'] = 'not integer';
                        break;
                    case 'float':
                        if (!is_float($value)) $errors['numeric'] = 'not float';
                        break;
                    default:
                        if (!is_numeric($value)) $errors['numeric'] = 'not numeric';
                        break;
                }
            }

            if ($required and (isset($argument['str']) or isset($argument['string'])) and !is_string($value)) {
                $errors['string'] = 'invalid';
            }

            if (!empty($argument['pattern']) and $strlen > 0) {
                if (!preg_match("/{$argument['pattern']}/", $value)) {
                    $errors['pattern'] = 'intalid';
                }
            }

            if (!empty($argument['is']) and $strlen > 0) {
                $is = strtolower($argument['is']);
                if ($required) {
                    $status = match ($is) {
                        'string' => is_string($value),
                        'numeric' => is_numeric(trim("$value")),
                        'int', 'integer' => filter_var($value, FILTER_VALIDATE_INT),
                        'float' => filter_var($value, FILTER_VALIDATE_FLOAT),
                        'datetime' => Date::dateTimeFormat($value),
                        'date' => Date::dateFormat($value),
                        'time' => Date::timeFormat($value),
                        default => true
                    };
                }
                if ($status) $status = match ($is) {
                    'email' => filter_var($value, FILTER_VALIDATE_EMAIL),
                    'domain' => filter_var($value, FILTER_VALIDATE_DOMAIN),
                    'ip' => filter_var($value, FILTER_VALIDATE_IP),
                    'url' => filter_var($value, FILTER_VALIDATE_URL),
                    default => true
                };

                if ($status === false) $errors['is'] = 'intalid';
            }
        }

        return (count($errors)) ? $errors : true;
    }
}
