<?php

namespace NeoCube\Form\Element;

use NeoCube\Form\ElementAbstract;

class Button extends ElementAbstract {

    public function __construct(?string $identify = null, ?string $type = null) {
        if ($identify) $this->label($identify);
        if (!$this->type and $type) $this->type = $type;
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

    public function isValid() {
        return true;
    }
}
