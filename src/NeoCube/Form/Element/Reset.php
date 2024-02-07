<?php

namespace NeoCube\Form\Element;

use NeoCube\Form\ElementAbstract;

class Reset extends ElementAbstract {

    protected string $type = 'reset';

    public function __construct($identify){
        $this->label($identify);
    }

    public function input(){
        return '<button type="'.$this->type().'" '.$this->attr(). '>'.$this->label().'</button>';
    }

}
