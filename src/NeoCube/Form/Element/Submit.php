<?php

namespace NeoCube\Form\Element;

use NeoCube\Form\ElementAbstract;

class Submit extends ElementAbstract {

    protected string $type = 'submit';

    public function __construct($identify){
        $this->label($identify);
    }

    /**
     * Seta atributo formnovalidate
     *
     * @param boolean $val
     * @return this
     */
    public function formNoValidate($val=NULL)   {
        if (is_null($val)){
            return isset($this->attr['formnovalidate'])?TRUE:FALSE;
        } else {
            if ((bool)$val) $this->attr['formnovalidate'] = TRUE;
            else  unset($this->attr['formnovalidate']);
            return $this;
        }
    }

    public function input(){
        return '<button type="'.$this->type().'" '.$this->attr(). '>'.$this->label().'</button>';
    }
}
