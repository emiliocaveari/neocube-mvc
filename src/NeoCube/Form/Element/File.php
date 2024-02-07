<?php

namespace NeoCube\Form\Element;

use NeoCube\Form\ElementAbstract;

class File extends ElementAbstract {

    protected string $type = 'file';

    public function accept(string $val=null) : self|string   {
        if (is_null($val)){
            return isset($this->attr['accept']) ? $this->attr['accept'] : '';
        } else {
            if ($val !== false) $this->attr['accept'] = trim($val);
            else  unset($this->attr['accept']);
            return $this;
        }
    }

}
