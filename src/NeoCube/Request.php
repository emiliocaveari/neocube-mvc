<?php

namespace NeoCube;

use NeoCube\Validate;

class Request {

    static protected $_data    = null;
    static protected $_method  = null;
    static protected $_headers = null;

    static private function get_input_values() : ?array {
        $values = file_get_contents("php://input") ?: array();
        if ($values){
            if ( isset($_SERVER['CONTENT_TYPE']) and $_SERVER['CONTENT_TYPE']=='application/json' ){
                $values = json_decode($values, true);
            }
        }
        return $values;
    }


    static protected function readRequestData() :void {
        static::$_method  = strtoupper($_SERVER['REQUEST_METHOD']);
        switch (static::$_method) {
            case 'POST':
                if (!$_POST) $_POST = static::get_input_values();
                static::$_data = $_POST;
                break;
            case 'GET':
                static::$_data = $_GET;
                break;
            case 'DELETE':
            case 'PUT':
                static::$_data = static::get_input_values();
                break;
        }

        if (isset($_FILES)){
            foreach ($_FILES as $key => $file) {
                static::$_data[$key] = $file;
            }
        }

        if (isset(static::$_data['_method'])){
            static::$_method = trim(static::$_data['_method']);
            unset(static::$_data['_method']);
        }
    }

    //--PUBLIC FUNCTIONS------------------------------------------------------//
    //------------------------------------------------------------------------//


    static public function request() :bool {
        if (is_null(static::$_data)) self::readRequestData();
        return (static::$_data) ? true : false;
    }

    static public function getData(bool|string|array $data=false, mixed $defaultEmpty=null) : mixed{
        if (is_null(static::$_data) or $data===true) self::readRequestData();
        if ($data!==true and $data!==false) {
            if ( is_array($data) ){
                $arrData = [];
                foreach ($data as $k){
                    $arrData[$k] = isset(static::$_data[$k]) ? static::$_data[$k] : $defaultEmpty;
                }
                return $arrData;
            }
            else return isset(static::$_data[$data]) ? static::$_data[$data] : $defaultEmpty;
        }
        else return static::$_data;
    }

    static public function getHeader(string $header='',bool $reload=false) :mixed {
        if ( is_null(static::$_headers) or $reload) static::$_headers = getallheaders();
        return empty($header) 
            ? static::$_headers 
            : ( isset(static::$_headers[$header]) ? static::$_headers[$header] : false );
    }

    static public function getMethod() : string {
        if (is_null(static::$_data)) self::readRequestData();
        return static::$_method;
    }
    static public function isMethod($method) : bool {
        if (is_null(static::$_data)) self::readRequestData();
        return ( static::$_method === strtoupper($method) );
    }

    //--Validaçao
    static public function validateData(array $arguments,$data=null) : bool {
        if (is_null($data)) $data = self::getData();
        return Validate::data($data,$arguments);
    }

    static public function validateErrors() : array{
        return Validate::getDataErrors();
    }


    //--Retorna array GET em formato de url
    static public function getToUrl(array $arr=[] ) : string {
        //--Mescalndo novos valores ao get
        if ( static::isMethod('get') ){
            $data = static::getData();
            $get  = array_merge($data,$arr);
        } else {
            $get = $arr;
        }
        return '?'.http_build_query($get); 
    }

    //--Verifia se a requisição foi realizada via XMLHttpRequest
    static public function isAjax() : bool  {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) and $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest')
            ? true
            : false;
    }


}
