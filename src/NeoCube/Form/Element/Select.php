<?php

namespace NeoCube\Form\Element;

use NeoCube\Form\ElementAbstract;

class Select extends ElementAbstract {

    private $options = [];
    protected string $type = 'select';


    /**
     * Seta options
     *
     * @param array $options array("value"=>"option") or
     * @param array $options array("value"=>array(attr=>value) )
     * @return this
     */
    public function options($options=array()){
        if (is_null($options) or !count($options)){
            return $this->options;
        } else {
            $this->options = $options;
            return $this;
        }
    }

    public function optionsValues() : array {
        $values = [];
        if (isset($this->options['optgroup'])){
            foreach ($this->options['optgroup'] as $opt ){
                if ( isset($opt['value']) ) 
                    $values = array_merge($values,array_keys($opt['value']));
                else 
                    $values = array_merge($values,array_keys($opt));
            }
        }
        else {
            $values = array_merge($values,array_keys($this->options));
        }
        return $values;
    }

    /**
     * Seta se o select será de multiplos valores
     *
     * @param array $options array("value"=>"option") or
     * @param array $options array("value"=>array(attr=>value) )
     * @return this
     */
    public function multiple(bool $bool=true) :self {
        $this->attr(array('multiple'=>$bool));
        return $this;
    }

    public function size(mixed $val = null) :mixed {
        if (is_null($val)){
            return isset($this->attr['size']) ? $this->attr['size'] : '';
        } else {
            if ($val !== false) $this->attr['size'] = $val;
            else  unset($this->attr['size']);
            return $this;
        }
    }

    public function getParamsValidate() :array {
        $name = $this->name(true);
        $params = array_filter($this->attr,function($val,$key){
            return in_array($key,[
                'max',
                'min',
                'required',
                'multiple',
            ]);
        },ARRAY_FILTER_USE_BOTH);
        $params['in_array'] = $this->optionsValues();
        return array($name=>$params);
    }




    public function input() :string {

        $options       = $this->options();
        $optionsSelect = $this->value();
        $this->value(false);

        //--Input
        $input = '<select '.$this->attr().'>'.PHP_EOL;

        foreach ($options as $opt_key => $opt_value) {
            if ( $opt_key == 'empty' ) {
                $input .= $this->renderOptions([''=>$opt_value],$optionsSelect);
            }
            else if ($opt_key == 'optgroup'){
                foreach ($opt_value as $key => $value) {
                    $input .= '<optgroup label="'.$key.'" ';
                    if ( isset($value['value']) and isset($value['attr']) ){
                        foreach ($value['attr'] as $k => $a) $input .= "{$k}=\"{$a}\" ";
                        $value = $value['value'];
                    }
                    $input .= '>';
                    $input .= $this->renderOptions($value,$optionsSelect);
                    $input .= '</optgroup>';
                }
                unset($options['optgroup']);
            }
            else {
                $input .= $this->renderOptions([$opt_key => $opt_value],$optionsSelect);
            }
        }

        $input .= "</select>";
        return $input;
    }


    private function renderOptions(array $options, mixed $optionsSelect) : string{
        $input  = '';
        $select = true; //--Selecionar apenas uma vez
        foreach ($options as $val=>$option) {
            $input .= '<option value="'.$val.'" ';
            //--Verificanco se é o valor setado para adicionar selected
            if ( ($optionsSelect == $val and $select) or ( is_array($optionsSelect) and in_array($val,$optionsSelect) ) ){
                $select = false;
                $input .= 'selected ';
            }

            if (is_array($option)){
                if (isset($option['option'])){
                    $desc = $option['option'];
                    unset($option['option']);
                } else {
                    $desc = array_shift($option);
                }
                foreach ($option as $att=>$value) {
                    if ($value === TRUE ) $input .= $att.' ';
                    else $input .= $att.'="'.$value.'" ';
                }
                $input .= '>'.$desc."</option>".PHP_EOL;
            } else {
                $input .= '>'.$option."</option>".PHP_EOL;
            }
        }
        return $input;
    }




}
