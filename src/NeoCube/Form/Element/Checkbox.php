<?php

namespace NeoCube\Form\Element;

use NeoCube\Form\ElementAbstract;

class Checkbox extends ElementAbstract {

    protected string $type = 'checkbox';

    /**
     * Seta atributo checked
     *
     * @param boolean $val
     * @return this
     */
    public function checked($val=NULL){
        if (is_null($val)){
            return isset($this->attr['checked'])?TRUE:FALSE;
        } else {
            if ((bool)$val) $this->attr['checked'] = TRUE;
            else  unset($this->attr['checked']);
            return $this;
        }
    }

    public function value($val=NULL) : mixed {
        if (is_null($val)){
            return isset($this->attr['value'])?$this->attr['value']:NULL;
        } else {
            //--Emilio 07-02-2014
            //--Se valor igual a 'on' seta como checked
            if ($val == 'on'){
                $this->attr['checked'] = TRUE;
                return $this;
            } else if (!isset($this->attr['value'])){
                return parent::value($val);
            } else {
                if($this->attr['value'] == $val) $this->attr['checked'] = TRUE;
                else unset($this->attr['checked']);
                return $this;
            }
        }
    }


}
