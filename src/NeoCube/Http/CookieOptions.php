<?php

namespace NeoCube\Http;

use InvalidArgumentException;

//--Regras dos atributos de cookie, em um só lugar: valem para todo cookie que
//--passa por RequestContext::addCookie() e para a sessão nativa do PHP.
final class CookieOptions {

    const KEYS      = ['httponly', 'secure', 'samesite', 'domain'];
    const SAME_SITE = ['Lax', 'Strict', 'None'];

    private function __construct() {}

    //--'lax' → 'Lax'; valor desconhecido → null
    static public function sameSite(mixed $value): ?string {
        $sameSite = ucfirst(strtolower((string) $value));
        return in_array($sameSite, self::SAME_SITE, true) ? $sameSite : null;
    }

    //--Opção desconhecida ou SameSite inválido é erro de programação: um cookie
    //--sem a proteção pedida não pode sair sem aviso.
    static public function normalize(array $options): array {
        if (!$options) return [];

        $unknown = array_diff(array_keys($options), self::KEYS);
        if ($unknown) throw new InvalidArgumentException('Unknown cookie option: ' . implode(', ', $unknown));

        if (isset($options['samesite'])) {
            $sameSite = self::sameSite($options['samesite']);
            if ($sameSite === null) throw new InvalidArgumentException("Invalid cookie samesite \"{$options['samesite']}\": use Lax, Strict or None");
            $options['samesite'] = $sameSite;

            //--navegadores recusam SameSite=None sem Secure
            if ($sameSite === 'None') $options['secure'] = true;
        }

        return $options;
    }
}
