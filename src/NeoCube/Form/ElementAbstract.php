<?php

namespace NeoCube\Form;

abstract class ElementAbstract {

    protected array $attr      = array();
    protected array $attrLabel = array();
    protected array $attrError = array();
    protected string $label    = '';
    protected string $type     = '';

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
        'rows',
        'title',
        'error'
    );


    public function __construct($identify) {
        $this->name($identify);
    }

    public function name(string|bool|null $val = null): string | static {
        if (is_null($val) or $val === true) {
            if (isset($this->attr['name'])) {
                $name = $this->attr['name'];
                //--apenas nome inicial
                if ($val === true) {
                    $pos = strpos($name, '[');
                    if ($pos !== false) $name = substr($name, 0, $pos);
                }
                return $name;
            }
            return '';
        } else {
            if ($val !== false) $this->attr['name'] = trim("{$val}");
            else  unset($this->attr['name']);
            return $this;
        }
    }

    public function type(): string {
        return $this->type;
    }

    public function options(null|array $options = array()): array|static {
        return $this;
    }

    public function rows(?string $val = null): string|static {
        return $this;
    }

    public function label(?string $val = null): string | static {
        if (is_null($val)) {
            if ($this->label) return $this->label;
            else if ($this->label !== false) {
                $name = (string) $this->attr('name');
                if ($name) return ucfirst($name);
                else return '';
            } else {
                return '';
            }
        } else {
            $this->label = $val;
            return $this;
        }
    }

    public function id(string|bool|null $val = null): string | static {
        if (is_null($val)) {
            return isset($this->attr['id']) ? $this->attr['id'] : '';
        } else {
            if ($val !== false) $this->attr['id'] = trim("{$val}");
            else  unset($this->attr['id']);
            return $this;
        }
    }

    public function value(array|string|bool|null $val = null): string | array | static {
        if (is_null($val)) {
            return isset($this->attr['value']) ? $this->attr['value'] : '';
        } else {
            if ($val === false) unset($this->attr['value']);
            else {
                if (is_array($val)) $this->attr['value'] = $val;
                else $this->attr['value'] = trim("{$val}");
            }
            return $this;
        }
    }

    public function maxlength(null|int|string $val = null): string|static {
        if (is_null($val)) {
            return isset($this->attr['maxlength']) ? $this->attr['maxlength'] : '';
        } else {
            if ($val !== false) $this->attr['maxlength'] = trim("{$val}");
            else  unset($this->attr['maxlength']);
            return $this;
        }
    }

    public function minlength(null|int|string $val = null): string|static {
        if (is_null($val)) {
            return isset($this->attr['minlength']) ? $this->attr['minlength'] : '';
        } else {
            if ($val !== false) $this->attr['minlength'] = trim("{$val}");
            else  unset($this->attr['minlength']);
            return $this;
        }
    }

    public function step(null|int|string|float $val = null): string|static {
        if (is_null($val)) {
            return isset($this->attr['step']) ? $this->attr['step'] : '';
        } else {
            if ($val !== false) $this->attr['step'] = trim("{$val}");
            else  unset($this->attr['step']);
            return $this;
        }
    }

    public function max(null|int|string $val = null): string|static {
        if (is_null($val)) {
            return isset($this->attr['max']) ? $this->attr['max'] : '';
        } else {
            if ($val !== false) $this->attr['max'] = trim("{$val}");
            else  unset($this->attr['max']);
            return $this;
        }
    }

    public function min(null|int|string $val = null): string|static {
        if (is_null($val)) {
            return isset($this->attr['min']) ? $this->attr['min'] : '';
        } else {
            if ($val !== false) $this->attr['min'] = trim("{$val}");
            else  unset($this->attr['min']);
            return $this;
        }
    }

    public function placeholder(null|bool|string $val = null): string|static {
        if (is_null($val)) {
            return isset($this->attr['placeholder']) ? $this->attr['placeholder'] : '';
        } else {
            if ($val !== false) $this->attr['placeholder'] = trim("{$val}");
            else  unset($this->attr['placeholder']);
            return $this;
        }
    }

    public function pattern(null|bool|string $val = null): string|static {
        if (is_null($val)) {
            return isset($this->attr['pattern']) ? $this->attr['pattern'] : '';
        } else {
            if ($val !== false) $this->attr['pattern'] = trim("{$val}");
            else  unset($this->attr['pattern']);
            return $this;
        }
    }

    public function readonly(?bool $val = null): bool|static {
        if (is_null($val)) {
            return isset($this->attr['readonly']) ? true : false;
        } else {
            if ($val) $this->attr['readonly'] = true;
            else unset($this->attr['readonly']);
            return $this;
        }
    }

    public function disabled(?bool $val = null): bool|static {
        if (is_null($val)) {
            return isset($this->attr['disabled']) ? true : false;
        } else {
            if ($val) $this->attr['disabled'] = true;
            else  unset($this->attr['disabled']);
            return $this;
        }
    }

    public function autofocus(?bool $val = null): bool|static {
        if (is_null($val)) {
            return isset($this->attr['autofocus']) ? true : false;
        } else {
            if ($val) $this->attr['autofocus'] = true;
            else  unset($this->attr['autofocus']);
            return $this;
        }
    }

    public function autocomplete(null|bool|string $val = null): null|string|static {
        if (is_null($val)) {
            return isset($this->attr['autocomplete']) ? $this->attr['autocomplete'] : null;
        } else {
            if (in_array($val, ['on', 'off'], true)) $this->attr['autocomplete'] = $val;
            else $this->attr['autocomplete'] = (((bool)$val) ? 'on' : 'off');
            return $this;
        }
    }

    public function required(?bool $val = null): bool|static {
        if (is_null($val)) {
            return isset($this->attr['required']) ? true : false;
        } else {
            if ($val) $this->attr['required'] = true;
            else  unset($this->attr['required']);
            return $this;
        }
    }

    public function title(null|string|bool $val = null): string|static {
        if (is_null($val)) {
            return isset($this->attr['title']) ? $this->attr['title'] : '';
        } else {
            if ($val !== false) $this->attr['title'] = trim("{$val}");
            else  unset($this->attr['title']);
            return $this;
        }
    }

    public function error(null|bool|array $val = null): static | array {
        if (is_null($val)) {
            return $this->attrError;
        } else {
            if ($val !== false) $this->attrError = $val;
            else  $this->attrError = null;
            return $this;
        }
    }

    public function attr(null|string|array $name = null, bool $unset = false): string|static {
        if (is_null($name)) {
            $rt = '';
            foreach ($this->attr as $att => $value) {
                if ($value === true) $rt .= $att . ' ';
                else if (!is_array($value)) $rt .= $att . '="' . $value . '" ';
            }
            return $rt;
        } else if (is_array($name)) {
            foreach ($name as $key => $value) {
                if (in_array(strtolower($key), $this->attrList)) {
                    $this->$key($value);
                } else {
                    if ($value !== false) {
                        if (isset($this->attr[$key])) $this->attr[$key] .= ' ' . $value;
                        else                          $this->attr[$key]  = $value;
                    } else {
                        unset($this->attr[$key]);
                    }
                }
            }
            return $this;
        } else {
            $rt = '';
            if (isset($this->attr[$name])) {
                $rt = $this->attr[$name];
                if ($unset) unset($this->attr[$name]);
            }
            return $rt;
        }
    }

    public function attrLabel(null|string|array $val = null): string|static {
        if (is_null($val)) {
            $rt = '';
            foreach ($this->attrLabel as $att => $value) $rt .= $att . '="' . $value . '" ';
            return $rt;
        } else if (is_array($val)) {
            foreach ($val as $key => $value) {
                if ($value !== false) {
                    if (isset($this->attrLabel[$key])) $this->attrLabel[$key] .= ' ' . $value;
                    else                               $this->attrLabel[$key]  = $value;
                } else {
                    unset($this->attrLabel[$key]);
                }
            }
            return $this;
        } else {
            return isset($this->attrLabel[$val]) ? $this->attrLabel[$val] : '';
        }
    }

    public function input(): string {
        $input = '<input type="' . $this->type() . '" ' . $this->attr() . '/>';
        return $input;
    }

    public function render(): string {
        return $this->input();
    }

    public function getParamsValidate(): array {
        $name = $this->name(true);
        $params = array_filter($this->attr, function ($val, $key) {
            return in_array($key, [
                'maxlength',
                'minlength',
                'max',
                'min',
                'required',
                'pattern'
            ]);
        }, ARRAY_FILTER_USE_BOTH);
        return array($name => $params);
    }
}
