<?php

namespace NeoCube;

use NeoCube\Request;
use NeoCube\Validate;
use NeoCube\Form\ElementAbstract;
use NeoCube\Form\FormRender;

class Form {

    protected array $attr       = [];
    protected array $attrError  = ['style' => 'box-shadow: 0 0 4px 1px #FF0000;'];
    protected array $elements   = [];

    protected array $error      = [];

    protected array $request    = [];           //--Valores preparados para registrar valores nos elemento]s
    protected array $elements_values_init = []; //--Valores setados inicialmente

    protected bool $open  = false;
    protected bool $close = false;

    protected string $FormRender  = '';         //--Classe de renderização do formulários
    protected string $funcRender  = 'render';   //--Função padrão de renderização do formulários

    protected array $mapElements = [
        'button' => '\\NeoCube\\Form\\Element\\Button',
        'captcha' => '\\NeoCube\\Form\\Element\\Captcha',
        'checkbox' => '\\NeoCube\\Form\\Element\\Checkbox',
        'color' => '\\NeoCube\\Form\\Element\\Color',
        'datalist' => '\\NeoCube\\Form\\Element\\Datalist',
        'date' => '\\NeoCube\\Form\\Element\\Date',
        'datetime' => '\\NeoCube\\Form\\Element\\Datetime',
        'datetimelocal' => '\\NeoCube\\Form\\Element\\Datetimelocal',
        'email' => '\\NeoCube\\Form\\Element\\Email',
        'file' => '\\NeoCube\\Form\\Element\\File',
        'hidden' => '\\NeoCube\\Form\\Element\\Hidden',
        'month' => '\\NeoCube\\Form\\Element\\Month',
        'number' => '\\NeoCube\\Form\\Element\\Number',
        'password' => '\\NeoCube\\Form\\Element\\Password',
        'radio' => '\\NeoCube\\Form\\Element\\Radio',
        'range' => '\\NeoCube\\Form\\Element\\Range',
        'reset' => '\\NeoCube\\Form\\Element\\Reset',
        'search' => '\\NeoCube\\Form\\Element\\Search',
        'select' => '\\NeoCube\\Form\\Element\\Select',
        'submit' => '\\NeoCube\\Form\\Element\\Submit',
        'tel' => '\\NeoCube\\Form\\Element\\Tel',
        'text' => '\\NeoCube\\Form\\Element\\Text',
        'textarea' => '\\NeoCube\\Form\\Element\\Textarea',
        'time' => '\\NeoCube\\Form\\Element\\Time',
        'url' => '\\NeoCube\\Form\\Element\\Url',
        'week' => '\\NeoCube\\Form\\Element\\Week',
    ];


    public function __construct(array $attr) {
        $this->attr = $attr;
        if (!isset($this->attr['action'])) $this->attr['action']  = '';
        if (!isset($this->attr['method'])) $this->attr['method']  = 'POST';
    }


    public function getError() {
        return $this->error;
    }


    /**
     * Atribui valor a name
     *
     * @param String $name
     * @return $this
     */
    final public function name(string|null $name = null): string|self {
        if (is_null($name)) {
            return $this->attr['name'];
        } else {
            if ($name != '' and $name !== false) $this->attr['name'] = trim($name);
            else  $this->attr['name'] = '';
            return $this;
        }
    }

    /**
     * Atribui valor a action
     *
     * @param String $action
     * @return $this
     */
    final public function action(string|null $action = null): string|self {
        if (is_null($action)) {
            return $this->attr['action'];
        } else {
            if ($action != '' and $action !== false) $this->attr['action'] = trim($action);
            else  $this->attr['action'] = '';
            return $this;
        }
    }

    /**
     * Atribui valor GET/POST a method
     *
     * @param String $method
     * @return $this
     */
    final public function method(string $method = ''): string|self {
        if (empty($method)) {
            return isset($this->attr['method']) ? $this->attr['method'] : '';
        } else {
            if (in_array(strtoupper($method), ['GET', 'POST']))
                $this->attr['method'] = strtoupper($method);
            return $this;
        }
    }

    /**
     * Atribui valor a enctype. Padrao multipart/form-data
     *
     * @param String $enctype
     * @return $this
     */
    final public function enctype(string|false $enctype = ''): string|self {
        if (empty($enctype)) {
            return isset($this->attr['enctype']) ? $this->attr['enctype'] : '';
        } else {
            if ($enctype !== false) $this->attr['enctype'] = trim($enctype);
            else  unset($this->attr['enctype']);
            return $this;
        }
    }


    final public function id(string|null $id = null): string|self {
        if ($id === null) {
            return $this->attr['id'] ?? '';
        } else {
            $this->attr['id'] = trim($id);
            return $this;
        }
    }


    /**
     * Atributos extas
     *
     * @param array $val Ex:array('style'=>'border:0')
     * @return null
     */
    final public function attr(array |null $val = null): string|self {
        if (is_null($val)) {
            return $this->attr;
        } else if (is_array($val)) {
            foreach ($val as $key => $value) {
                if (!in_array(strtolower($key), array('method', 'enctype', 'action', 'name'))) {
                    if ($value !== false)
                        if (isset($this->attr[$key])) $this->attr[$key] .= ' ' . $value;
                        else                          $this->attr[$key]  = $value;
                    else
                        unset($this->attr[$key]);
                }
            }
            return $this;
        } else {
            return isset($this->attr[$val]) ? $this->attr[$val] : '';
        }
    }

    /**
     * Atributos inseridos ao elemento ao ser validado como false
     *
     * @param array $val Ex:array('style'=>'border:0')
     * @return $this
     */
    final public function attrError(array $val = []): array|self {
        if (count($val)) {
            $this->attrError = $val;
            return $this;
        } else {
            return $this->attrError;
        }
    }


    /**
     * Seta função para renderizar elementos do formulário
     *
     * @param string $formRender , padrão Render::class;
     * @return $this
     */
    final public function formRender(string $formRender = ''): self|callable {
        if (empty($formRender)) {
            return $this->FormRender;
        } else {
            $this->FormRender = $formRender;
        }
        return $this;
    }

    /**
     * Seta função para renderizar elementos do formulário
     *
     * @param string $funcRender , padrão 'render';
     * @return $this
     */
    final public function funcRender(string $funcRender = ''): self | callable {
        if (empty($funcRender)) {
            return $this->funcRender;
        } else {
            $this->funcRender = $funcRender;
        }
        return $this;
    }


    private function pushElement(ElementAbstract $element, string $identify = '', string $pos = ''): void {
        if (!empty($identify)) {
            if (!empty($pos)) {
                if (!isset($this->elements[$identify]) or !is_array($this->elements[$identify]))
                    $this->elements[$identify] = array();
                $this->elements[$identify][$pos] = $element;
            } else if (isset($this->elements[$identify])) {
                if (is_array($this->elements[$identify])) {
                    $this->elements[$identify][] = $element;
                } else {
                    $auxElement = $this->elements[$identify];
                    $this->elements[$identify] = array();
                    $this->elements[$identify][] = $auxElement;
                    $this->elements[$identify][] = $element;
                }
            } else {
                $this->elements[$identify] = $element;
            }
        } else {
            $this->elements[] = $element;
        }
    }

    /**
     * Adiciona um elemento
     *
     * @param string $type text,button,select..
     * @param NeoCube_Element $type
     * @param string $identify identifica no array de elementos o novo elemento adicionado
     * @return NeoCube_Element
     */
    public function addElement(ElementAbstract|string $type, string $identify = '', null|string $pos = ''): ElementAbstract | false {
        if ($type instanceof ElementAbstract) {
            $element = $type;
        } else {
            $class_name = $this->mapElements[$type] ?? '\\NeoCube\\Form\\Element\\' . ucfirst(strtolower($type));
            if (class_exists($class_name)) {
                $element = new $class_name($identify);
            } else {
                return false;
            }
        }
        $this->pushElement($element, $identify, $pos);
        return $element;
    }


    /**
     * Adiciona array contendo objetos de NeoCube_Element
     *
     * @param array $elements Ex: array('identify'=>$element);
     * @return Null
     */
    public function addElements(array $elements): void {
        foreach ($elements as $identify => $element)
            if ($element instanceof ElementAbstract)
                $this->addElement($element, $identify);
    }

    /**
     * Retorna elemento NeoCube_Element
     *
     * @param string $identify
     * @param intefer $pos=0 Caso elementos com mesmo nome (Ex:radio)
     * @return NeoCube_Element
     */
    final public function getElement(string $identify, string|null $pos = '', bool $remove = false): ElementAbstract | array | null {
        $elem = null;
        if (isset($this->elements[$identify])) {
            if (is_array($this->elements[$identify]) and !empty($pos)) {
                if (isset($this->elements[$identify][$pos]))
                    $elem = $this->elements[$identify][$pos];
                if ($remove) unset($this->elements[$identify][$pos]);
            } else {
                $elem = $this->elements[$identify];
                if ($remove) unset($this->elements[$identify]);
            }
        }
        return $elem;
    }

    /**
     * Remove elemento NeoCube_Element
     *
     * @param string $identify
     * @param intefer $pos=0 Caso elementos com mesmo nome (Ex:radio)
     * @return NeoCube_Element
     */
    final public function removeElement(string $identify, string|null $pos = ''): bool {
        if (isset($this->elements[$identify])) {
            if (is_array($this->elements[$identify]) and !empty($pos)) {
                unset($this->elements[$identify][$pos]);
            } else {
                unset($this->elements[$identify]);
            }
            return true;
        }
        return false;
    }

    /**
     * Verifica se existe elemento com a identificação passada
     *
     * @param string $identify
     * @return boolean
     */
    final public function issetElement(string $identify, $pos = ''): bool {
        if (isset($this->elements[$identify])) {
            if (empty($pos)) return true;
            if (isset($this->elements[$identify][$pos])) return true;
        }
        return false;
    }

    /**
     * Retorna Element->name
     *
     * @return Array
     */
    final public function getElementsName(): array {
        $arr = array();
        foreach ($this->elements as $elem) {
            if (is_array($elem)) {
                foreach ($elem as $el) {
                    $name = $el->name(true);
                    if (!in_array($name, $arr) and !empty($name)) {
                        $arr[] = $name;
                    }
                }
            } else {
                $name = $elem->name(true);
                if (!in_array($name, $arr) and !empty($name)) {
                    $arr[] = $name;
                }
            }
        }
        return $arr;
    }



    /**
     * Retorna parametros de validaçao dos elementos do form
     *
     * @return Array
     */
    final public function getElementsParamsValidate(): array {
        $arr = array();
        foreach ($this->elements as $elem) {
            if (is_array($elem)) {
                foreach ($elem as $el) {
                    foreach ($el->getParamsValidate() as $key => $value) {
                        $arr[$key] = $value;
                    }
                }
            } else {
                foreach ($elem->getParamsValidate() as $key => $value) {
                    $arr[$key] = $value;
                }
            }
        }
        return $arr;
    }


    /**
     * Escreve html do elemento
     *
     * @param string $identify Pode ser passado um elemento ou varios elmentos separados por "," Ex: 'element' ou 'element1,element2'
     * @param string $fieldset Agrupa elementos em um fieldset com legenda
     * @param array  $attr adiciona atributos ao fieldset Ex (array('id'=>'num_id'))
     * @return html
     */
    public function writeElement(array|string $identify, string $pos = ''): string {

        //--Verifica se existe constante definida para renderizar o formulário
        if (empty($this->FormRender))
            $this->FormRender = Env::getValue('CLASS_FORM_RENDER') ?: FormRender::class;

        //--Retorno renderizado dos elementos
        $return = '';
        $elementsRender = array();

        //--Se passou uma instancia do elemento do form
        if ($identify instanceof ElementAbstract) {
            $elementsRender[] = $identify;
        }
        //--Passou string de identificação de um elemento do form
        else {

            if (is_string($identify) and strpos($identify, ',') !== false) {
                $identify = explode(',', $identify);
            }
            //--Se passar um array de identificadores
            if (is_array($identify)) {
                foreach ($identify as $id) {
                    if (isset($this->elements[$id])) {
                        //--se for array de elementos
                        if (is_array($this->elements[$id])) {
                            foreach ($this->elements[$id] as $elem)
                                $elementsRender[] = $elem;
                        } else {
                            $elementsRender[] = $this->elements[$id];
                        }
                        unset($this->elements[$id]);
                    }
                }
            } else if (isset($this->elements[$identify])) {
                //--se for array de elementos
                if (is_array($this->elements[$identify])) {
                    if (empty($pos)) {
                        foreach ($this->elements[$identify] as $elem)
                            $elementsRender[] = $elem;
                        unset($this->elements[$identify]);
                    } else if (isset($this->elements[$identify][$pos])) {
                        $elementsRender[] = $this->elements[$identify][$pos];
                        unset($this->elements[$identify][$pos]);
                    }
                } else {
                    $elementsRender[] = $this->elements[$identify];
                    unset($this->elements[$identify]);
                }
            }
        }

        //--Renderiza elemento
        if ($elementsRender) {
            $funcRender = $this->funcRender;
            $formRender = $this->FormRender;
            $return = $formRender::$funcRender($elementsRender);
        }

        return $return;
    }


    /**
     * Abre tag <form>
     *
     * @return html
     */
    public function open(): string {
        if (!$this->open) {
            $return = '<form ';
            foreach ($this->attr as $key => $val) {
                $val = trim($val);
                if ($val === null) $return .= $key . ' ';
                else          $return .= $key . '="' . $val . '" ';
            }
            $return .= '>' . PHP_EOL;
            $this->open = true;
            return $return;
        } else {
            return '';
        }
    }

    /**
     * Retorna elemento
     */
    public function element(string $identify, string $pos = '0'): ?ElementAbstract {

        if (isset($this->elements[$identify])) {
            if (is_array($this->elements[$identify])) {
                $return = $this->elements[$identify][$pos];
                unset($this->elements[$identify][$pos]);
            } else {
                $return = $this->elements[$identify];
                unset($this->elements[$identify]);
            }
            return $return;
        } else {
            return null;
        }
    }

    /**
     * Fecha tag </form>
     *
     * @return html
     */
    public function close(): string {
        if (!$this->close) {
            $this->close = true;
            return '</form>';
        } else {
            return '';
        }
    }


    /**
     * Retorna formulário
     *
     * @return html
     */
    public function writeForm($funcRender = null): string {
        //--seta função de renderização do form
        if ($funcRender !== null and $funcRender !== false)
            $this->funcRender($funcRender);

        //--Inicia renderização
        $return = $this->open();

        $keys = array_keys($this->elements);
        $return .= $this->writeElement($keys);

        if ($funcRender !== false)
            $return .= $this->close();

        return $return;
    }


    /**
     * Verifica que o formulario foi enviado
     * @param bool $reload Le novamente os campos recebidos
     *
     * @return bool
     */
    public function request(bool $reload = false): bool {
        if (!$this->request or $reload) {
            $names   = $this->getElementsName();
            $request = Request::getData($reload);
            if ($request and is_array($request)) {
                foreach ($request as $key => $value) {
                    if (in_array($key, $names)) $this->request[$key] = $value;
                }
            }
        }
        return ($this->request) ? true : false;
    }


    /**
     * Retorna dados enviados pelo formulario
     *
     * @return array
     */
    public function requestValues(): ?array {
        if ($this->request()) {
            return $this->request;
        } else {
            return null;
        }
    }


    /**
     * Valida elementos do formulario
     *
     * @return bool
     */
    public function validate(array $arguments = []): bool {
        if (!$arguments) $arguments = $this->getElementsParamsValidate();
        if (Validate::data($this->requestValues(), $arguments)) {
            return true;
        } else {
            $this->registerValues();
            $this->registerValidateErrors(Validate::getDataErrors());
            return false;
        }
    }


    /**
     * Restaura/Limpa values dos elementos
     *
     * @param boolean $init restaura valores iniciais DEFAULT true
     * @param array $only_identify restaura somente valores indentificados
     * @return this
     */
    public function clearValues(bool $init = true, array $only_identify = []): self {

        //--Verifica se foram registrados valores
        if (is_null($this->elements_values_init)) return $this;

        //--Registrando valores nos elementos
        foreach ($this->elements as $identify => $element) {
            if (count($only_identify) == 0 or in_array($identify, $only_identify)) {
                if (is_array($element)) {
                    foreach ($element as $key => $elem) {
                        if (isset($this->elements_values_init[$identify][$key]) and $init) {
                            $val = $this->elements_values_init[$identify][$key];
                            $elem->value($val);
                        } else {
                            $elem->value(false);
                        }
                    }
                } else {
                    if (isset($this->elements_values_init[$identify]) and $init) {
                        $val = $this->elements_values_init[$identify];
                        $element->value($val);
                    } else {
                        $element->value(false);
                    }
                }
            }
        }
        return $this;
    }


    /**
     * Adiciona values aos campos
     *
     * @param array $values
     * @return html
     */
    public function registerValues(array $values = []): self {
        //--Validando valores
        if (count($values)) {
            $values = $this->prepareValuesForRegister($values);
        } else if (count($this->request)) {
            $values = $this->prepareValuesForRegister($this->request);
        } else {
            return $this;
        }
        //--Registrando valores nos elementos
        foreach ($this->elements as $identify => $element) {
            if (is_array($element)) {
                foreach ($element as $key => $elem) {
                    //--Seta valor inicial
                    $this->elements_values_init[$identify][$key] = $elem->value();
                    //--atributo name do elemento
                    $elemName = $elem->name();
                    //--Verifica se foi usado para receber dados de array sequencial
                    if (substr($elemName, strlen($elemName) - 2) == '[]') {
                        $elemName = substr($elemName, 0, strlen($elemName) - 2);
                        $arrValues = null;
                        $i = 0;
                        while (isset($values[$elemName . '[' . $i . ']'])) {
                            $arrValues[$i] = $values[$elemName . '[' . $i . ']'];
                            $i++;
                        }
                        $elem->value($arrValues);
                    } else if (isset($values[$elemName])) {
                        $val = $values[$elemName];
                        if (is_array($val)) $val = $val[$key];
                        $elem->value($val);
                    }
                }
            } else {
                $this->elements_values_init[$identify] = $element->value();
                //--atributo name do elemento
                $elemName = $element->name();
                //--Verifica se foi usado para receber dados de array sequencial
                if (substr($elemName, strlen($elemName) - 2) == '[]') {
                    $elemName = substr($elemName, 0, strlen($elemName) - 2);
                    $arrValues = array();
                    $i = 0;
                    while (isset($values[$elemName . '[' . $i . ']'])) {
                        $arrValues[$i] = $values[$elemName . '[' . $i . ']'];
                        $i++;
                    }
                    $element->value($arrValues);
                } else if (isset($values[$elemName])) {
                    $val = $values[$elemName];
                    $element->value($val);
                }
            }
        }
        return $this;
    }



    /**
     * Adiciona values aos campos
     *
     * @param array $values
     * @return html
     */
    public function registerValidateErrors(array $errors): self {
        //--Registrando erros nos elementos
        foreach ($this->elements as $identify => $element) {
            if (is_array($element)) {
                foreach ($element as $key => $elem) {
                    //--atributo name do elemento
                    $name = $elem->name(true);
                    if (isset($errors[$name])) {
                        $elem->attr(['error' => $errors[$name]]);
                    }
                }
            } else {
                $name = $element->name(true);
                if (isset($errors[$name])) {
                    $element->attr(['error' => $errors[$name]]);
                }
            }
        }
        return $this;
    }


    private function prepareValuesForRegister(array $values): array {
        foreach ($values as $key => $val) {
            if (is_array($val) and count($val)) {
                $return = $this->prepareValuesForRegister($val);
                foreach ($return as $k => $v) {
                    if (substr($k, -1) == ']') {
                        $pos = strpos($k, '[');
                        $k1  = substr($k, 0, $pos);
                        $k2  = substr($k, $pos);
                        $name[$key . '[' . $k1 . ']' . $k2] = $v;
                    } else {
                        $name[$key . '[' . $k . ']'] = $v;
                    }
                }
            } else {
                $name[$key] = $val;
            }
        }
        return $name;
    }
}
