<?php

namespace NeoCube\View\Tag;

class ScriptTag {

    private array $attributes = [];

    public function __construct(
        private string $type,
        private string $content = '',
        string|array $attributes = ''
    ) {
        if (is_string($attributes)) {
            $expl = array_filter(explode(' ', $attributes));
            $attributes = array_reduce($expl, function ($acc, $item) {
                if (strpos($item, '=') !== false) {
                    list($k, $v) = explode('=', str_replace('"', '', $item), 2);
                    $acc[trim($k)] = trim("$v");
                } else $acc[trim("$item")] = '';
                return $acc;
            }, []);
        }
        if ($attributes)
            $this->attributes = array_merge($this->attributes, $attributes);
    }

    public function getAttributes(): array {
        return $this->attributes;
    }
    public function getAttribute(string $key): string {
        return $this->attributes[$key] ?? '';
    }
    public function issetAttribute(string $key): bool {
        return isset($this->attributes[$key]) ? true : false;
    }
    public function getContent(): string {
        return $this->content;
    }

    public function render(): string {
        $attr_string = '';
        foreach ($this->attributes as $k => $v)
            $attr_string .=  " {$k}=\"{$v}\"";

        return in_array($this->type, ['meta', 'link'])
            ? "<{$this->type}{$attr_string}>"
            : "<{$this->type}{$attr_string}>{$this->content}</{$this->type}>";
    }
}
