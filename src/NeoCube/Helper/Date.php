<?php

namespace NeoCube\Helper;

class Date {


    const BR_MONTH = [
        1 => 'Janeiro',
        2 => 'Fevereiro',
        3 => 'Março',
        4 => 'Abril',
        5 => 'Maio',
        6 => 'Junho',
        7 => 'Julho',
        8 => 'Agosto',
        9 => 'Setembro',
        10 => 'Outubro',
        11 => 'Novembro',
        12 => 'Dezembro',
    ];

    //--Emilio 05-07-2022
    //--Retorna mes
    static public function brMonth(int $num,bool $short=false) : ?string {
        $num = intval($num);
        return isset(self::BR_MONTH[$num])
            ? ( $short ? substr(self::BR_MONTH[$num],0,3) : self::BR_MONTH[$num])
            : null;
    }


    //--Emilio 24-03-2014
    //--Formata datas para YYYY-MM-DD
    static public function dateFormat(string &$date) :bool {
        if ( preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/',$date) ) { //-- YYYY-MM-DD
            return true;
        } else if ( preg_match('/^[0-9]{2}\/[0-9]{2}\/[0-9]{4}$/',$date) ){ //-- DD/MM/YYYY
            $date = implode('-',array_reverse(explode('/',$date)));
            return true;
        } else if ( preg_match('/^[0-9]{2}-[0-9]{2}-[0-9]{4}$/',$date) ) { //-- DD-MM-YYYY
            $date = implode('-',array_reverse(explode('-',$date)));
            return true;
        } else {
            $date = null;
            return false;
        }
    }

    //--Emilio 03-02-2015
    //--Verifica hora
    static public function timeFormat(string &$time) :bool {
        if ( strlen($time) == 5 ) $time = $time .= ':00';
        if ( preg_match('/^[012]{1}[0-9]{1}:[012345]{1}[0-9]{1}:[012345]{1}[0-9]{1}$/',$time) ){
            return true;
        } else {
            $time = null;
            return false;
        }
    }

    //--Emilio 03-02-2015
    //--Verifica data e hora (datetime) YYYY-MM-DD HH:MM:SS
    static public function dateTimeFormat(string &$datetime) :bool {
        $date = substr($datetime,0,10);
        $time = substr($datetime,11);
        $datetime = null;

        if ( empty($time) ) $time = '00:00:00';
        else if ( !static::timeFormat($time) ) return false;

        if ( !static::dateFormat($date) ) return false;

        $datetime = $date .' '. $time;
        return true;
    }

    //--Emilio 15-04-2014
    //--Calcula diferenca entre datas
    //--Retorna horas ou minutos
    static public function dateTimeDiff(string $date_begin,string|null $date_end=null,string $type_return='S') : int|false {

        if ( !self::dateTimeFormat($date_begin) ) return false;
        if ( is_null($date_end)  ) $date_end  = date('Y-m-d H:i:s');

        try {
            $DtBegin = new \DateTime($date_begin);
            $DtEnd   = new \DateTime($date_end);
        } catch (\Throwable $e) {
            return false;
        }

        $interval = $DtBegin->diff($DtEnd);
        switch ($type_return) {
            case 'Y':
            case 'Year':
                return $interval->y;
                break;
            case 'M':
            case 'Month':
                return ($interval->y*12) + $interval->m; //--total de meses
                break;
            case 'D':
            case 'Day':
                return $interval->days; //--total de dias
                break;
            case 'H':
            case 'Hour':
                $diff = ($interval->days * 24) + $interval->h; //--horas
                return floor($diff);
                break;
            case 'I':
            case 'Minute':
                return ((($interval->days * 24) + $interval->h) * 60) + $interval->m; //--minutos
                break;
            case 'S':
            case 'Second':
            default:
                return ((((($interval->days * 24) + $interval->h) * 60) + $interval->m) * 60) + $interval->s; //--segundos
                break;
        }
    }


    //--Emilio 13-01-2016
    //--Retorna data atual com soma da quantidade passada
    static public function dateTimeAdd(string|null $date=null,array $quant=array('H'=>1), string $return="Y-m-d H:i:s") : ?string {

        //--formada datatime
        if ( is_null($date) ) $date = date('Y-m-d H:i:s');
        else static::dateTimeFormat($date);

        if ( preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})$/", $date,$dt1) ){

            $arrTime = array(
                'Y' => isset($quant['Y']) ? $quant['Y'] : 0,
                'M' => isset($quant['M']) ? $quant['M'] : 0,
                'D' => isset($quant['D']) ? $quant['D'] : 0,
                'H' => isset($quant['H']) ? $quant['H'] : 0,
                'I' => isset($quant['I']) ? $quant['I'] : 0,
                'S' => isset($quant['S']) ? $quant['S'] : 0
            );

            //-- $dt1
            // [0] => 2012-12-16 10:48:42
            // [1] => 2012 -- ano
            // [2] => 12   -- mes
            // [3] => 16   -- dia
            // [4] => 10   -- hora
            // [5] => 48   -- minuto
            // [6] => 42   -- segundo

            //-- Monta MKTime
            //-- mktime(hour,minute,second,month,day,year)
            $mktime = mktime(
                $dt1[4]+$arrTime['H'],
                $dt1[5]+$arrTime['I'],
                $dt1[6]+$arrTime['S'],
                $dt1[2]+$arrTime['M'],
                $dt1[3]+$arrTime['D'],
                $dt1[1]+$arrTime['Y']
            );

            //--Retorna dada com os calculos de acordo com tipo de retorno
            switch ($return) {
                case 'D':
                case 'Date':
                    $return = 'Y-m-d';
                    break;
                case 'T':
                case 'Time':
                    $return = 'H:i:s';
                    break;
                case 'DT':
                case 'DateTime':
                    $return = 'Y-m-d H:i:s';
                    break;
                case 'LongDate':
                    return self::longDate(date('Y-m-d',$mktime));
            }

            return date($return,$mktime);
        } else {
            return null;
        }
    }

    //--Emilio 03-11-2014
    //--Retorna dada por extenso
    //--Ex: 03/11/2014 => 03 de Novembro de 2014
    static public function longDate(string $date) :string {
        //--trata data
        if ( static::dateFormat($date) ){
            //--dividade
            $arr = explode('-',$date);
            $long = $arr[2].' de '.self::brMonth($arr[1]).' de '.$arr[0];
            return $long;
        } else {
            return false;
        }
    }

}
