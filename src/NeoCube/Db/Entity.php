<?php

namespace NeoCube\Db;

use NeoCube\Db\Mapper as DbMapper;
use NeoCube\Helper\Strings;

class Entity {

    protected ?DbMapper $mapper;
    protected array     $data = [];

    public function __construct(?array $data = null) {
        if ($data) $this->data = $data;
    }


    /**
     * @param $name
     * @param $value
     */
    public function __set(string $name, $value): void {
        $setName = Strings::toCamelCase("set_$name");
        if (method_exists($this, $setName)) $this->$setName($value);
        else $this->data[$name] = $value;
    }

    /**
     * @param $name
     * @return bool
     */
    public function __isset($name): bool {
        return isset($this->data[$name]);
    }

    /**
     * @param $name
     * @return string|null
     */
    public function __get($name) {

        $camelName = Strings::toCamelCase($name, '_');
        if (method_exists($this, $camelName)) return $this->$camelName();
        if (method_exists($this, $name))      return $this->$name();

        //--findo to getName
        $getName = Strings::toCamelCase("get_$name", '_');
        if (method_exists($this, $getName)) return $this->$getName($name);

        //--findName TO find_name
        $name_under = Strings::reverseCamelCase($name, '_');
        return isset($this->data[$name])
            ? $this->data[$name]
            : ($this->data[$name_under] ?? null);
    }
}
