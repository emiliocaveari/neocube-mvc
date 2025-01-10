<?php

namespace NeoCube\Form\Element;

use NeoCube\Form\ElementAbstract;

class Checkbox extends ElementAbstract {

    protected string $type = 'checkbox';

    public function checked($val = null) {
        if (is_null($val)) {
            return isset($this->attr['checked']) ? true : false;
        } else {
            if ((bool)$val) $this->attr['checked'] = true;
            else  unset($this->attr['checked']);
            return $this;
        }
    }

    public function value(string|bool|null $val = null): string|static {
        if (is_null($val)) {
            return isset($this->attr['value']) ? $this->attr['value'] : '';
        } else {
            //--Emilio 07-02-2014
            //--Se valor igual a 'on' seta como checked
            if ($val == 'on') {
                $this->attr['checked'] = true;
                return $this;
            } else if (!isset($this->attr['value'])) {
                return parent::value($val);
            } else {
                if ($this->attr['value'] == $val) $this->attr['checked'] = true;
                else unset($this->attr['checked']);
                return $this;
            }
        }
    }
}
