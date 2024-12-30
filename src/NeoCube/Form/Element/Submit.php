<?php

namespace NeoCube\Form\Element;

use NeoCube\Form\ElementAbstract;

class Submit extends ElementAbstract {

    protected string $type = 'submit';

    public function __construct($identify) {
        $this->label($identify);
    }

    public function formNoValidate(null|bool $val = null): bool|static {
        if (is_null($val)) {
            return isset($this->attr['formnovalidate']) ? true : false;
        } else {
            if ((bool)$val) $this->attr['formnovalidate'] = true;
            else  unset($this->attr['formnovalidate']);
            return $this;
        }
    }

    public function input(): string {
        return '<button type="' . $this->type() . '" ' . $this->attr() . '>' . $this->label() . '</button>';
    }
}
