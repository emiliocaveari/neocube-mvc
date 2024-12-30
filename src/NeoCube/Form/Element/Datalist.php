<?php

namespace NeoCube\Form\Element;

use NeoCube\Form\ElementAbstract;

class Datalist extends ElementAbstract {

    protected string $type = 'text';

    private $options = array();
    private $attrDatalist = [];

    /**
     * Seta atributo id do datalist
     *
     * @param string $val return this element
     * @return this
     */
    public function idDatalist($val=null) {
        if (is_null($val)){
            return isset($this->attrDatalist['id'])?$this->attrDatalist['id']:null;
        } else {
            if ($val !== false) $this->attrDatalist['id'] = trim($val);
            else  unset($this->attrDatalist['id']);
            return $this;
        }
    }


    /**
     * Seta atributos de datalist
     *
     * @param array $val array("attr"=>"value")
     * @return this
     */
    public function attrDatalist($val=null,$unset=false){
        if (is_null($val)){
            $rt = '';
            foreach ($this->attrDatalist as $att=>$value) {
                if ($value === true ) $rt .= $att.' ';
                else if (!is_array($value)) $rt .= $att.'="'.$value.'" ';
            }
            return $rt;
        } else if (is_array($val)) {
            foreach ($val as $key=>$value){
                if ($value !== false){
                    if (isset($this->attrDatalist[$key])) $this->attrDatalist[$key] .= ' '.$value;
                    else                          $this->attrDatalist[$key]  = $value;
                } else {
                    unset($this->attrDatalist[$key]);
                }
            }
            return $this;
        } else {
            $rt = null;
            if ( isset($this->attrDatalist[$val])) {
                $rt = $this->attrDatalist[$val];
                if ( $unset ) unset($this->attrDatalist[$val]);
            }
            return $rt;
        }
    }

    public function options(null|array $options=array()):array|static{
        if (is_null($options) or !count($options)){
            return $this->options;
        } else {
            $this->options = $options;
            return $this;
        }
    }


    public function input() : string{
        if ( !$this->id() )         $this->id( ($this->name() . rand(111111,999999)) );
        if ( !$this->idDatalist() ) $this->idDatalist($this->id().'-datalist');
        $this->attr('list',true); //--Remove list caso tenha sido setado

        $input = '<input type="text" '.$this->attr().' list="'.$this->idDatalist().'" />'.PHP_EOL;
        $input .= '<datalist '.$this->attrDatalist().' >'.PHP_EOL;

        $options = $this->options();
        foreach ($options as $option) {
            $input .= '<option ';
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
        $input .= "</datalist>";
        return $input;
    }

}
