<?php

namespace NeoCube\Form\Element;

use NeoCube\Form\ElementAbstract;

class Date extends ElementAbstract {

    protected string $type = 'date';


    public function getParamsValidate(): array {
        $name = $this->name(true);
        $params = array_filter($this->attr, function ($val, $key) {
            return in_array($key, [
                'max',
                'min',
                'required',
            ]);
        }, ARRAY_FILTER_USE_BOTH);
        $params['dateformat'] = true;
        return array($name => $params);
    }
}
