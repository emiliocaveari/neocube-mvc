<?php

namespace NeoCube\Form\Element;

use NeoCube\Form\ElementAbstract;

class Radio extends ElementAbstract {

    protected string $type = 'radio';

    public function checked(null|bool $val = null): bool|static {
        if (is_null($val)) {
            return isset($this->attr['checked']) ? true : false;
        } else {
            if ($val) $this->attr['checked'] = true;
            else  unset($this->attr['checked']);
            return $this;
        }
    }

    public function value(array|string|bool|null $val = null): string | array | static {
        if (is_null($val)) {
            return isset($this->attr['value']) ? $this->attr['value'] : null;
        } else {
            if (!isset($this->attr['value'])) {
                return parent::value($val);
            } else {
                if ($this->attr['value'] == $val) $this->attr['checked'] = true;
                else unset($this->attr['checked']);
                return $this;
            }
        }
    }
}
