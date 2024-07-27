<?php

namespace NeoCube\Helper;

class Strings {

    static public function strToUrl(string $str): string {
        $str = preg_replace("/&([a-z])[a-z]+;/i", "$1", htmlentities($str, ENT_NOQUOTES, 'UTF-8'));
        return strtolower(str_replace(' ', '_', preg_replace("/[^a-zA-Z0-9 _\-\.]+/", "", trim($str))));
    }


    static public function ripTags(string $string): string {
        $string = str_replace([
            "\r\n",
            "\r",
            "\n",
            "\t",
            "  "
        ], ' ', $string);
        return $string;
    }


    static public function cpfCnpjFriendly(string $doc): string {
        $doc = preg_replace("/[^0-9]/", "", $doc);
        $len = strlen($doc);

        if ($len == 11) $doc = preg_replace('/([0-9]{3})([0-9]{3})([0-9]{3})([0-9]{2})/', '$1.$2.$3-$4', $doc);
        else if ($len == 14) $doc = preg_replace('/([0-9]{2})([0-9]{3})([0-9]{3})([0-9]{4})([0-9]{2})/', '$1.$2.$3/$4-$5', $doc);

        return $doc;
    }

    static public function telFriendly(string $num): string {
        $num = preg_replace('/[^0-9]/', '', $num);
        $len = strlen($num);

        if ($len == 8) $num = preg_replace('/([0-9]{4})([0-9]{4})/', '$1-$2', $num);
        else if ($len == 9) $num = preg_replace('/([0-9]{5})([0-9]{4})/', '$1-$2', $num);
        else if ($len == 10) $num = preg_replace('/([0-9]{2})([0-9]{4})([0-9]{4})/', '($1)$2-$3', $num);
        else if ($len == 11) $num = preg_replace('/([0-9]{2})([0-9]{5})([0-9]{4})/', '($1)$2-$3', $num);
        else if ($len == 12) $num = preg_replace('/([0-9]{2})([0-9]{2})([0-9]{4})([0-9]{4})/', '+$1 ($2)$3-$4', $num);
        else if ($len == 13) $num = preg_replace('/([0-9]{2})([0-9]{2})([0-9]{5})([0-9]{4})/', '+$1 ($2)$3-$4', $num);

        return $num;
    }


    static public function onlyNumbers(string $str): string {
        return preg_replace("/[^0-9]/", "", $str);
    }

    static public function toCamelCase(string $string, string|array $separator = '-'): string {
        $camelCase = str_replace(' ', '', ucwords(str_replace($separator, ' ', strtolower($string))));
        $camelCase[0] = strtolower($camelCase[0]);
        return $camelCase;
    }

    static public function toPascalCase(string $string, string|array $separator = '-'): string {
        $pascalCase = str_replace(' ', '', ucwords(str_replace($separator, ' ', strtolower($string))));
        return $pascalCase;
    }

    static public function reverseCamelCase(string $str, string $separator = '-'): string {
        $str = lcfirst($str);
        $str = preg_replace("/[A-Z]/", $separator . "$0", $str);
        return strtolower($str);
    }

    static public function reversePascalCase(string $str, string $separator = '-'): string {
        return self::reverseCamelCase($str, $separator);
    }
}
