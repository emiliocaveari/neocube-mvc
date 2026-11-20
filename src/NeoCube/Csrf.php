<?php

namespace NeoCube;

//--Proteção CSRF por token sincronizado com a sessão (opt-in: nada é
//--verificado se o módulo não chamar verify()/isValid()).
//--
//--  Formulário:  $form->csrf()     → campo oculto _csrf (ver Form)
//--               ou Csrf::field()  ecoado em formulário escrito à mão
//--  AJAX:        header X-CSRF-Token com o valor de Csrf::token() (ex.: lido de uma <meta>)
//--  Verificação: if (!Csrf::verify()) ... — no Controller base do módulo
//--               ou num Bootstrap::beforeRequest. GET/HEAD/OPTIONS passam.
//--
//--Depois do login, chame regenerate(): o token anterior ao login não vale mais.
final class Csrf {

    const FIELD       = '_csrf';
    const HEADER      = 'X-CSRF-Token';
    const SESSION_KEY = 'NEOCUBE_CSRF_TOKEN';

    const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    private function __construct() {}

    //--Token da sessão atual (criado no primeiro uso)
    static public function token(): string {
        $token = Session::get(self::SESSION_KEY);
        if (!is_string($token) or $token === '') {
            $token = bin2hex(random_bytes(32));
            Session::set(self::SESSION_KEY, $token);
        }
        return $token;
    }

    //--Gera um token novo (ex.: depois do login)
    static public function regenerate(): string {
        Session::clear(self::SESSION_KEY);
        return self::token();
    }

    //--<input type="hidden" name="_csrf" value="...">
    static public function field(): string {
        return '<input type="hidden" name="' . self::FIELD . '" value="' . self::token() . '">';
    }

    //--O token enviado (campo _csrf ou header X-CSRF-Token; ou $token) confere
    //--com o da sessão? Sessão sem token = inválido (nunca gera um aqui)
    static public function isValid(?string $token = null): bool {
        $expected = Session::get(self::SESSION_KEY);
        if (!is_string($expected) or $expected === '') return false;

        $token ??= self::submittedToken();
        return is_string($token) and $token !== '' and hash_equals($expected, $token);
    }

    //--Métodos seguros passam; os demais exigem token válido
    static public function verify(): bool {
        if (in_array(Request::getMethod(), self::SAFE_METHODS, true)) return true;
        return self::isValid();
    }

    static private function submittedToken(): ?string {
        $header = Request::getHeader(self::HEADER);
        if (is_string($header) and $header !== '') return $header;

        $field = Request::getData(self::FIELD);
        return is_string($field) ? $field : null;
    }
}
