<?php

namespace NeoCube;

use Closure;
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

    protected array $mapElements = [];


    public function __construct(array $attr) {
        $this->attr = $attr;
        if (!isset($this->attr['action'])) $this->attr['action']  = '';
        if (!isset($this->attr['method'])) $this->attr['method']  = 'POST';
    }


    public function getError() {
        return $this->error;
    }


    final public function name(string|null $name = null): string|static {
        if (is_null($name)) {
            return $this->attr['name'];
        } else {
            if ($name != '' and $name !== false) $this->attr['name'] = trim($name);
            else  $this->attr['name'] = '';
            return $this;
        }
    }

    final public function action(string|null $action = null): string|static {
        if (is_null($action)) {
            return $this->attr['action'];
        } else {
            if ($action != '' and $action !== false) $this->attr['action'] = trim($action);
            else  $this->attr['action'] = '';
            return $this;
        }
    }

    final public function method(string $method = ''): string|static {
        if (empty($method)) {
            return isset($this->attr['method']) ? $this->attr['method'] : '';
        } else {
            if (in_array(strtoupper($method), ['GET', 'POST']))
                $this->attr['method'] = strtoupper($method);
            return $this;
        }
    }

    final public function enctype(string|false $enctype = ''): string|static {
        if (empty($enctype)) {
            return isset($this->attr['enctype']) ? $this->attr['enctype'] : '';
        } else {
            if ($enctype !== false) $this->attr['enctype'] = trim($enctype);
            else  unset($this->attr['enctype']);
            return $this;
        }
    }


    final public function id(string|null $id = null): string|static {
        if ($id === null) {
            return $this->attr['id'] ?? '';
        } else {
            $this->attr['id'] = trim($id);
            return $this;
        }
    }

    final public function attr(array |null $val = null): string|static {
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

    final public function attrError(array $val = []): array|static {
        if (count($val)) {
            $this->attrError = $val;
            return $this;
        } else {
            return $this->attrError;
        }
    }


    final public function formRender(string $formRender = ''): static|callable {
        if (empty($formRender)) {
            return $this->FormRender;
        } else {
            $this->FormRender = $formRender;
        }
        return $this;
    }

    final public function funcRender(string $funcRender = ''): static | callable {
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

    public function addElements(array $elements): void {
        foreach ($elements as $identify => $element)
            if ($element instanceof ElementAbstract)
                $this->addElement($element, $identify);
    }

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

    final public function issetElement(string $identify, $pos = ''): bool {
        if (isset($this->elements[$identify])) {
            if (empty($pos)) return true;
            if (isset($this->elements[$identify][$pos])) return true;
        }
        return false;
    }

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


    public function formatElements(true|array|string $identify, Closure $format): static {
        if ($identify === true)
            $identify = array_keys($this->elements);
        else if (is_string($identify) and strpos($identify, ',') !== false)
            $identify = explode(',', $identify);

        if (is_array($identify)) {
            foreach ($identify as $id) {
                if (isset($this->elements[$id])) {
                    if (is_array($this->elements[$id])) {
                        foreach ($this->elements[$id] as $elem)
                            $format($elem,$id);
                    } else $format($this->elements[$id],$id);
                }
            }
        } else if (isset($this->elements[$identify])) {
            if (is_array($this->elements[$identify])) {
                foreach ($this->elements[$identify] as $elem)
                    $format($elem,$identify);
            } else $format($this->elements[$identify],$identify);
        }
        return $this;
    }


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

    public function close(): string {
        if (!$this->close) {
            $this->close = true;
            return '</form>';
        } else {
            return '';
        }
    }


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


    public function request(bool $reload = false): bool {
        if (
            Request::isMethod($this->method()) and
            (!$this->request or $reload) 
        ) {
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


    public function requestValues(): ?array {
        if ($this->request()) {
            return $this->request;
        } else {
            return null;
        }
    }


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


    public function clearValues(bool $init = true, array $only_identify = []): static {

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


    public function registerValues(array $values = []): static {
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



    public function registerValidateErrors(array $errors): static {
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
