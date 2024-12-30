<?php

namespace NeoCube\Form\Element;

use NeoCube\Form\ElementAbstract;
use NeoCube\Session;

class Captcha extends ElementAbstract {

    protected string $type = 'number';

    private $token;


    public function __construct($identify){
        $this->name($identify);
        //--criando token
        $val1 = rand(1,9);
        $val2 = rand(1,9);
        $this->token = ($val1 + $val2);
        $this->label = $val1 .' + '.$val2 .' = ?';
        $this->placeholder($val1 .' + '.$val2 .' = ?');
    }

    public function input() : string{
        //--Registra token na sessao
        Session::set('captcha_'.$this->name(true) , $this->token );
        //--Input
        $input = '<input type="'.$this->type().'" '.$this->attr().'/>';
        return $input;
    }


    /**
     * Retorna dados para validação do NeoCube_Form
     *
     * @return array
     */
    public function getParamsValidate() : array{
        $name = $this->name(true);
        $params = array(
            'numeric'  => true,
            'required' => true,
            'captcha'  => Session::get('captcha_'.$name,true)
        );
        return array($name=>$params);
    }


}
