<?php

namespace NeoCube\Db;

use NeoCube\Db\Mapper as DbMapper;
use NeoCube\Helper\Strings;

class Entity implements \ArrayAccess {

    protected ?DbMapper $_mapper;
    protected array     $_data = [];

    public function __construct(?array $data = null) {
        if ($data) $this->_data = $data;
    }

    public function offsetExists(mixed $offset): bool {
        return $this->__get($offset) !== null;
    }

    public function offsetGet(mixed $offset): mixed {
        return $this->__get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void {
        if (is_null($offset)) $this->_data[] = $value;
        else $this->__set($offset, $value);
    }

    public function offsetUnset(mixed $offset): void {
        unset($this->_data[$offset]);
    }

    public function __set(string $name, $value): void {
        $setName = Strings::toCamelCase("set_$name");
        if (method_exists($this, $setName)) $this->$setName($value);
        else $this->_data[$name] = $value;
    }

    public function __isset($name): bool {
        return isset($this->_data[$name]);
    }

    public function __get($name): mixed {

        $camelName = Strings::toCamelCase($name, '_');
        if (method_exists($this, $camelName)) return $this->$camelName();
        if (method_exists($this, $name))      return $this->$name();

        $getName = Strings::toCamelCase("get_$name", '_');
        if (method_exists($this, $getName)) return $this->$getName($name);

        $name_under = Strings::reverseCamelCase($name, '_');
        return $this->_data[$name]
            ?? $this->_data[$name_under]
            ?? $this->_data[$camelName]
            ?? null;
    }

    public function __call($name, $arguments): mixed {
        $camelName = Strings::toCamelCase($name, '_');
        $name_under = Strings::reverseCamelCase($name, '_');
        return $this->_data[$name]
            ?? $this->_data[$name_under]
            ?? $this->_data[$camelName]
            ?? null;
    }
}
