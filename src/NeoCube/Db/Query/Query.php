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
        $this->params->duplicateKey   = false;
        $this->params->conflictColumns = array();
        $this->params->limit          = null;
        $this->params->offset         = null;
        $this->params->group          = null;
        $this->params->having         = null;
        $this->params->alias          = null;
        $this->params->returning      = null;
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
            case 'offset':
                return $this->params->offset;
            case 'group':
                return $this->params->group;
            case 'having':
                return $this->params->having;
            case 'join':
                return $this->params->join;
            case 'alias':
                return $this->params->alias;
            case 'returning':
                return $this->params->returning;
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
        $this->params->offset = $offset;
        return $this;
    }

    public function setGroup(string $group, string $having = ''): static {
        $this->params->group = $group;
        $this->params->having = $having;
        return $this;
    }

    public function setReturning(string|array $cols): static {
        if (!is_array($cols))
            $cols = strpos($cols, ',') !== false ? explode(',', $cols) : [$cols];
        $this->params->returning = array_map('trim', $cols);
        return $this;
    }

    public function setForUpdate(bool $bool): static {
        $this->params->forUpdate = $bool;
        return $this;
    }

    public function setJoin(string $table, array $on, string $type = '', string $alias = ''): static {
        $this->params->join["{$table}{$alias}"] = [
            'table' => $table,
            'alias' => $alias,
            'type' => $type,
            'on' => $on
        ];
        return $this;
    }

    public function setDuplicateKey(bool|array $data, array $conflictColumns = []): static {
        if (!is_array($data)) {
            $this->params->duplicateKey = $data;
        } else {
            $this->params->duplicateKey = [];
            foreach ($data as $key => $val) {
                if ($val === 0 or $val === '0' or !empty($val) or is_null($val)) {
                    $this->params->duplicateKey[$key] = $val;
                }
            }
        }
        $this->params->conflictColumns = $conflictColumns;
        return $this;
    }


    public function setAlias(?string $alias): static {
        $this->params->alias = $alias;
        return $this;
    }


    public function clear(array $clear = []): static {

        if (empty($clear))
            $clear = ['data', 'cols', 'where', 'order', 'join', 'forupdate', 'duplicateKey', 'limit', 'group', 'returning'];

        if (in_array('data', $clear))           $this->params->data = array();
        if (in_array('cols', $clear))           $this->params->cols = array();
        if (in_array('where', $clear))          $this->params->where = array();
        if (in_array('order', $clear))          $this->params->order = array();
        if (in_array('join', $clear))           $this->params->join = array();
        if (in_array('forupdate', $clear))      $this->params->forUpdate = false;
        if (in_array('duplicateKey', $clear)) {
            $this->params->duplicateKey = false;
            $this->params->conflictColumns = array();
        }
        if (in_array('returning', $clear))      $this->params->returning = null;

        if (in_array('limit', $clear)) {
            $this->params->limit  = null;
            $this->params->offset = null;
        }
        if (in_array('group', $clear)) {
            $this->params->group  = null;
            $this->params->having = null;
        }

        return $this;
    }

    public function hasParam(string $type = ''): bool {
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
            case 'offset':
                return !is_null($this->params->offset);
            case 'alias':
                return !is_null($this->params->alias);
            case 'group':
                return !is_null($this->params->group);
            case 'having':
                return !is_null($this->params->having);
            case 'join':
                return !!count($this->params->join);
            case 'returning':
                return !empty($this->params->returning);
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
