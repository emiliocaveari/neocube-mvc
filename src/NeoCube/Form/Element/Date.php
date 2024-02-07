<?php

namespace NeoCube\Form\Element;

use NeoCube\Form\ElementAbstract;
use NeoCube\Helper\Date as HDate;

class Date extends ElementAbstract {

    protected string $type = 'date';


    public function getParamsValidate(){
        $name = $this->name(true);
        $params = array_filter($this->attr,function($val,$key){
            return in_array($key,[
                'max',
                'min',
                'required',
            ]);
        },ARRAY_FILTER_USE_BOTH);
        $params['dateformat'] = true;
        return array($name=>$params);
    }

}
