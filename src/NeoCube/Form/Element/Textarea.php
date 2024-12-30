<?php

namespace NeoCube\Form\Element;

use NeoCube\Form\ElementAbstract;

class Textarea extends ElementAbstract {

    protected string $type = 'textarea';


    public function rows(?string $val=null) :string|static {
        if (is_null($val)){
            return isset($this->attr['rows'])?$this->attr['rows']:'';
        } else {
            if ($val !== false) $this->attr['rows'] = $val;
            else  unset($this->attr['rows']);
            return $this;
        }
    }


    public function input() :string {
    	$value = $this->value();
    	$this->value(false);

        $input = '<textarea '.$this->attr().'>'.$value.'</textarea>';
    	$this->value($value);

        return $input;
    }

}
