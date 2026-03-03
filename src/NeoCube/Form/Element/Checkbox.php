<?php

namespace NeoCube\Form\Element;

use NeoCube\Form\ElementAbstract;

class Checkbox extends ElementAbstract {

    protected string $type = 'checkbox';

    public function value(array|string|bool|null $val = null): string | array | static {
        if (is_null($val)) {
            return isset($this->attr['value']) ? $this->attr['value'] : '';
        } else {
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
