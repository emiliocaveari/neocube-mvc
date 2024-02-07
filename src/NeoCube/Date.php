<?php

namespace NeoCube;

class Date extends \DateTime {


    public function dateTimeDiff($DtEnd=null,$type_return='S',$abs=false){

        if (is_null($DtEnd) ){
            $DtEnd  = new \DateTime();
        } else if ( !($DtEnd instanceof \DateTime) ){
            try {
                $DtEnd  = new \DateTime($DtEnd);
            } catch (\Throwable $e) {
                return false;
            }
        }

        $interval = $this->diff($DtEnd);
        switch ($type_return) {
            case 'Y':
            case 'Year':
                $diff =  $interval->y;
                break;
            case 'M':
            case 'Month':
                $diff =  ($interval->y*12) + $interval->m; //--total de meses
                break;
            case 'D':
            case 'Day':
                $diff =  $interval->days; //--total de dias
                break;
            case 'H':
            case 'Hour':
                $diff = ($interval->days * 24) + $interval->h; //--horas
                break;
            case 'I':
            case 'Minute':
                $diff =  ((($interval->days * 24) + $interval->h) * 60) + $interval->m; //--minutos
                break;
            case 'S':
            case 'Second':
            default:
                $diff =  ((((($interval->days * 24) + $interval->h) * 60) + $interval->m) * 60) + $interval->s; //--segundos
                break;
        }

        if (!$abs and $interval->invert==1) $diff = $diff * -1;

        return $diff;

    }

    //--Emilio 03-11-2014
    //--Retorna dada por extenso
    //--Ex: 03/11/2014 => 03 de Novembro de 2014
    public function longDate(){
        $long = $this->format('d') . ' de ';
        //--Selecionando Mes
        switch ($this->format('m')) {
            case '01': $long .= 'Janeiro';   break;
            case '02': $long .= 'Fevereiro'; break;
            case '03': $long .= 'Março';     break;
            case '04': $long .= 'Abril';     break;
            case '05': $long .= 'Maio';      break;
            case '06': $long .= 'Junho';     break;
            case '07': $long .= 'Julho';     break;
            case '08': $long .= 'Agosto';    break;
            case '09': $long .= 'Setembro';  break;
            case '10': $long .= 'Outubro';   break;
            case '11': $long .= 'Novembro';  break;
            case '12': $long .= 'Dezembro';  break;
        }
        $long .= ' de '.$this->format('Y');
        return $long;
    }

}
