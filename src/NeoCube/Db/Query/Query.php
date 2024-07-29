<?php

namespace NeoCube\Db\Query;

use stdClass;

class Query {

    private ?stdClass $params;
    private array $sentenceData = [];

    final public function __construct(string $table) {
        $this->params = new stdClass();
        $this->params->table          = $table;
        $this->params->data           = array();
        $this->params->cols           = array();
        $this->params->where          = array();
        $this->params->order          = array();
        $this->params->join           = array();
        $this->params->forUpdate      = false;
        $this->params->onDuplicateKey = false;
        $this->params->limit          = null;
        $this->params->limitOffset    = null;
        $this->params->group          = null;
        $this->params->groupHaving    = null;
    }

    public function getParams(): stdClass {
        return $this->params;
    }

    public function getParam(string $type = ''): mixed {
        switch (strtolower($type)) {
            case 'table':
                return $this->params->table;
            case 'data':
                return $this->params->data;
            case 'cols':
                return $this->params->cols;
            case 'where':
                return $this->params->where;
            case 'order':
                return $this->params->order;
            case 'limit':
                return $this->params->limit;
            case 'group':
                return $this->params->group;
            case 'join':
                return $this->params->join;
            default:
                return null;
        }
    }

    public function setSentenceData(array $data): static {
        $this->sentenceData = $data;
        return $this;
    }
    public function getSentenceData(): array {
        return $this->sentenceData;
    }

    public function setData(array $data) {
        $this->params->data = array_merge($this->params->data, $data);
        return $this;
    }

    public function setCols(string|array $cols): static {
        if (!is_array($cols)) {
            if (strpos($cols, ',') !== false)
                $cols = array($cols);
            else
                $cols = explode(',', $cols);
        }

        foreach ($cols as $query) {
            $c = static::formatCol($query);
            if (!isset($this->params->cols[$c])) $this->params->cols[$c] = $query;
        }

        return $this;
    }

    public function setWhere(string $cond): static {
        $this->params->where[] = $cond;
        return $this;
    }

    public function setOrder(array|string $order): static {
        if (!is_array($order)) {
            if (strpos($order, ',') !== false)
                $order = array($order);
            else
                $order = explode(',', $order);
        }
        $this->params->order = array_merge($this->params->order, $order);
        return $this;
    }

    public function setLimit(int $limit, int $offset = 0): static {
        $this->params->limit = $limit;
        $this->params->limitOffset = $offset;
        return $this;
    }

    public function setGroup(string $group, string $having = ''): static {
        $this->params->group = $group;
        $this->params->groupHaving = $having;
        return $this;
    }

    public function setForUpdate(bool $bool): static {
        $this->params->forUpdate = $bool;
        return $this;
    }

    public function setJoin(string $table, array $on, string $type = ''): static {
        $this->params->join[$table] = [
            'table' => $table,
            'type' => $type,
            'on' => $on
        ];
        return $this;
    }

    public function onDuplicateKey(bool|array $data): static {
        if (!is_array($data)) {
            $this->params->onDuplicateKey = $data;
        } else {
            foreach ($data as $key => $val) {
                if ($val === 0 or $val === '0' or !empty($val) or is_null($val)) {
                    $this->params->onDuplicateKey[$key] = $val;
                }
            }
        }
        return $this;
    }


    public function clear(array $clear = []): static {

        if (empty($clear))
            $clear = ['data', 'cols', 'where', 'order', 'limit', 'group', 'join', 'forupdate', 'onduplicatekey'];

        if (in_array('data', $clear))           $this->params->data = array();
        if (in_array('cols', $clear))           $this->params->cols = array();
        if (in_array('where', $clear))          $this->params->where = array();
        if (in_array('order', $clear))          $this->params->order = array();
        if (in_array('join', $clear))           $this->params->join = array();
        if (in_array('forupdate', $clear))      $this->params->forUpdate = false;
        if (in_array('onduplicatekey', $clear)) $this->params->onDuplicateKey = false;

        if (in_array('limit', $clear)) {
            $this->params->limit          = null;
            $this->params->limitOffset   = null;
        }
        if (in_array('group', $clear)) {
            $this->params->group          = null;
            $this->params->groupHaving   = null;
        }

        return $this;
    }

    public function isSetParam(string $type = ''): bool {
        switch (strtolower($type)) {
            case 'data':
                return !!count($this->params->data);
            case 'cols':
                return !!count($this->params->cols);
            case 'where':
                return !!count($this->params->where);
            case 'order':
                return !!count($this->params->order);
            case 'limit':
                return !is_null($this->params->limit);
            case 'group':
                return !is_null($this->params->group);
            case 'join':
                return !!count($this->params->join);
            default:
                return false;
        }
    }


    final static public function formatCol(string $col): string {
        $point = strrpos($col, '.');
        if ($point !== false) $col = substr($col, $point + 1);
        $as = strrpos(strtolower($col), ' as ');
        if ($as !== false) {
            $col = str_replace(' ', '', substr($col, $as + 3));
        }
        return $col;
    }
}
