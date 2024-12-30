<?php

namespace NeoCube\Form\Element;

use NeoCube\Form\ElementAbstract;

class Button extends ElementAbstract {

    protected string $type = 'button';

    public function __construct($identify){
        $this->label($identify);
    }

    public function input() : string{
        return '<button type="'.$this->type().'" '.$this->attr(). '>'.$this->label().'</button>';
    }

    public function isValid(){
        return TRUE;
    }

}
