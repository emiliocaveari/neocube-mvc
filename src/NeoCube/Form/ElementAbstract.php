<?php

namespace NeoCube\Form;

abstract class ElementAbstract {

    protected array $attr      = array();
    protected array $attrLabel = array();
    protected array $attrError = array();
    protected string $label    = '';
    protected string $type     = '';

    //--Atributos que sao tratados por set e get
    protected $attrList = array(
        'label',
        'attrlabel',
        'type',
        'name',
        'id',
        'value',
        'maxlength',
        'minlength',
        'readonly',
        'disabled',
        'step',
        'max',
        'min',
        'placeholder',
        'autofocus',
        'autocomplete',
        'required',
        'pattern',
        'formnovalidate',
        'options',
        'title',
        'error'
    );


    public function __construct($identify){
        $this->name($identify);
    }


    /**
     * Seta atributo name="$val"
     *
     * @param string $val
     * @return this
     */
    public function name($val=null) {
        if (is_null($val) or $val===true){
            if (isset($this->attr['name'])) {
                $name = $this->attr['name'];
                //--apenas nome inicial
                if ($val===true){
                    $pos = strpos($name,'[');
                    if ( $pos !== false ) $name = substr($name,0,$pos);
                }
                return $name;
            }
            return '';
        } else {
            if ($val !== false) $this->attr['name'] = trim($val);
            else  unset($this->attr['name']);
            return $this;
        }
    }

    /**
     * Retorna atributo type
     *
     * @return string
     */
    public function type(){ return $this->type; }

    /**
     * Seta atributo label
     *
     * @param string $val
     * @return this
     */
    public function label($val=null)   {
        if (is_null($val)){
            if ( $this->label ) return $this->label;
            else if ( $this->label !== false ){
                $name = (string) $this->attr('name');
                if ( $name ) return ucfirst($name);
                else return '';
            } else {
                return '';
            }
        } else {
            $this->label = $val;
            return $this;
        }
    }


    /**
     * Seta atributo id
     *
     * @param string $val return this element
     * @return this
     */
    public function id($val=null) {
        if (is_null($val)){
            return isset($this->attr['id'])?$this->attr['id']:'';
        } else {
            if ($val !== false) $this->attr['id'] = trim($val);
            else  unset($this->attr['id']);
            return $this;
        }
    }

    /**
     * Seta atributo value="$val"
     *
     * @param string $val
     * @return this
     */
    public function value($val=null) : mixed  {
        if (is_null($val)){
            return isset($this->attr['value'])?$this->attr['value']:'';
        } else {
            if ($val !== false) $this->attr['value'] = $val;
            else  unset($this->attr['value']);
            return $this;
        }
    }

    /**
     * Seta atributo maxlength
     *
     * @param integer $val
     * @return this
     */
    public function maxlength($val=null) {
        if (is_null($val)){
            return isset($this->attr['maxlength'])?$this->attr['maxlength']:'';
        } else {
            if ($val !== false) $this->attr['maxlength'] = trim($val);
            else  unset($this->attr['maxlength']);
            return $this;
        }
    }


    /**
     * Seta atributo minlength
     *
     * @param integer $val
     * @return this
     */
    public function minlength($val=null) {
        if (is_null($val)){
            return isset($this->attr['minlength'])?$this->attr['minlength']:'';
        } else {
            if ($val !== false) $this->attr['minlength'] = trim($val);
            else  unset($this->attr['minlength']);
            return $this;
        }
    }

    /**
     * Seta atributo step
     *
     * @param integer $val
     * @return this
     */
    public function step($val=null) {
        if (is_null($val)){
            return isset($this->attr['step'])?$this->attr['step']:'';
        } else {
            if ($val !== false) $this->attr['step'] = trim($val);
            else  unset($this->attr['step']);
            return $this;
        }
    }

    /**
     * Seta atributo max
     *
     * @param integer $val
     * @return this
     */
    public function max($val=null) {
        if (is_null($val)){
            return isset($this->attr['max'])?$this->attr['max']:'';
        } else {
            if ($val !== false) $this->attr['max'] = trim($val);
            else  unset($this->attr['max']);
            return $this;
        }
    }

    /**
     * Seta atributo min
     *
     * @param integer $val
     * @return this
     */
    public function min($val=null) {
        if (is_null($val)){
            return isset($this->attr['min'])?$this->attr['min']:'';
        } else {
            if ($val !== false) $this->attr['min'] = trim($val);
            else  unset($this->attr['min']);
            return $this;
        }
    }

    /**
     * Seta atributo placeholder
     *
     * @param integer $val
     * @return this
     */
    public function placeholder($val=null) {
        if (is_null($val)){
            return isset($this->attr['placeholder'])?$this->attr['placeholder']:'';
        } else {
            if ($val !== false) $this->attr['placeholder'] = trim($val);
            else  unset($this->attr['placeholder']);
            return $this;
        }
    }

    /**
     * Seta atributo pattern
     *
     * @param integer $val
     * @return this
     */
    public function pattern($val=null) {
        if (is_null($val)){
            return isset($this->attr['pattern'])?$this->attr['pattern']:'';
        } else {
            if ($val !== false) $this->attr['pattern'] = trim($val);
            else  unset($this->attr['pattern']);
            return $this;
        }
    }


    /**
     * Seta atributo readonly
     *
     * @param boolean $val
     * @return this
     */
    public function readonly($val=null)   {
        if (is_null($val)){
            return isset($this->attr['readonly'])?true:false;
        } else {
            if ((bool)$val) $this->attr['readonly'] = true;
            else  unset($this->attr['readonly']);
            return $this;
        }
    }

    /**
     * Seta atributo disabled
     *
     * @param boolean $val
     * @return this
     */
    public function disabled($val=null)   {
        if (is_null($val)){
            return isset($this->attr['disabled'])?true:false;
        } else {
            if ((bool)$val) $this->attr['disabled'] = true;
            else  unset($this->attr['disabled']);
            return $this;
        }
    }

    /**
     * Seta atributo autofocus
     *
     * @param boolean $val
     * @return this
     */
    public function autofocus($val=null)   {
        if (is_null($val)){
            return isset($this->attr['autofocus'])?true:false;
        } else {
            if ((bool)$val) $this->attr['autofocus'] = true;
            else  unset($this->attr['autofocus']);
            return $this;
        }
    }


    /**
     * Seta atributo autofocus
     *
     * @param boolean $val
     * @return this
     */
    public function autocomplete($val=null)   {
        if (is_null($val)){
            return isset($this->attr['autocomplete']) ? $this->attr['autocomplete'] : null;
        } else {
            if ( in_array($val,['on','off'],true) ) $this->attr['autocomplete'] = $val;
            else $this->attr['autocomplete'] = (((bool)$val) ? 'on' : 'off');
            return $this;
        }
    }

    /**
     * Seta atributo required
     *
     * @param boolean $val
     * @return this
     */
    public function required($val=null)   {
        if (is_null($val)){
            return isset($this->attr['required'])?true:false;
        } else {
            if ((bool)$val) $this->attr['required'] = true;
            else  unset($this->attr['required']);
            return $this;
        }
    }

    /**
     * Seta atributo title="$val"
     *
     * @param string $val
     * @return this
     */
    public function title($val=null) {
        if (is_null($val)){
            return isset($this->attr['title'])?$this->attr['title']:'';
        } else {
            if ($val !== false) $this->attr['title'] = trim($val);
            else  unset($this->attr['title']);
            return $this;
        }
    }


    /**
     * Seta atributo title="$val"
     *
     * @param string $val
     * @return this
     */
    public function error(null|bool|array $val=null) :self | array {
        if (is_null($val)){
            return $this->attrError;
        } else {
            if ($val !== false) $this->attrError = $val;
            else  $this->attrError = null;
            return $this;
        }
    }


    public function attr(null|string|array $name=null,bool $unset=false ) : string|self {
        if (is_null($name)){
            $rt = '';
            foreach ($this->attr as $att=>$value) {
                if ($value === true ) $rt .= $att.' ';
                else if (!is_array($value)) $rt .= $att.'="'.$value.'" ';
            }
            return $rt;
        } else if (is_array($name)) {
            foreach ($name as $key=>$value){
                if (in_array(strtolower($key),$this->attrList)){
                    $this->$key($value);
                } else {
                    if ($value !== false){
                        if (isset($this->attr[$key])) $this->attr[$key] .= ' '.$value;
                        else                          $this->attr[$key]  = $value;
                    } else {
                        unset($this->attr[$key]);
                    }
                }
            }
            return $this;
        } else {
            $rt = '';
            if ( isset($this->attr[$name])) {
                $rt = $this->attr[$name];
                if ( $unset ) unset($this->attr[$name]);
            }
            return $rt;
        }
    }

    /**
     * Seta atributos do label
     *
     * @param array $val array("attr"=>"value")
     * @return this
     */
    public function attrLabel($val=null){
        if (is_null($val)){
            $rt = '';
            foreach ($this->attrLabel as $att=>$value) $rt .= $att.'="'.$value.'" ';
            return $rt;
        }else if (is_array($val)) {
            foreach ($val as $key=>$value){
                if ($value !== false){
                    if (isset($this->attrLabel[$key])) $this->attrLabel[$key] .= ' '.$value;
                    else                               $this->attrLabel[$key]  = $value;
                } else {
                    unset($this->attrLabel[$key]);
                }

            }
            return $this;
        } else {
            return isset($this->attrLabel[$val])?$this->attrLabel[$val]:'';
        }
    }


    /**
     * Escreve apenas o elemento html
     *
     * @return string html
     */
    public function input(){
        //--Input
        $input = '<input type="'.$this->type().'" '.$this->attr().'/>';
        return $input;
    }


    //--Renderiza View
    public function render(){
        return $this->input();
    }



    /**
     * Retorna dados para validação do NeoCube_Form
     *
     * @return array
     */
    public function getParamsValidate(){
        $name = $this->name(true);
        $params = array_filter($this->attr,function($val,$key){
            return in_array($key,[
                'maxlength',
                'minlength',
                'max',
                'min',
                'required',
                'pattern'
            ]);
        },ARRAY_FILTER_USE_BOTH);
        return array($name=>$params);
    }



}
