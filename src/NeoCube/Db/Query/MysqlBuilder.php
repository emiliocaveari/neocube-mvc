<?php

namespace NeoCube\Db\Query;

use Closure;
use NeoCube\Application;

class MysqlBuilder implements BuilderInferface {

    public function select(Query $query): string {
        $queryParams = $query->getParams();

        $sentence  = 'SELECT ';
        $sentence .= (count($queryParams->cols)) ? implode(',', $queryParams->cols) : '*';
        $sentence .= ' FROM ' . $queryParams->table;

        if (count($queryParams->join))
            foreach ($queryParams->join as $join)
                $sentence .= ' ' . strtoupper($join['type']) . ' JOIN ' . $join['table'] . ' ON ' . implode(' AND ', $join['on']);

        if (count($queryParams->where))
            $sentence .= ' WHERE ' . implode(' AND ', $queryParams->where);

        if ($queryParams->group) {
            $sentence .= ' GROUP BY ' . $queryParams->group;
            if ($queryParams->groupHaving) $sentence .= ' HAVING ' . $queryParams->groupHaving;
        }

        if (count($queryParams->order))
            $sentence .= ' ORDER BY ' . implode(', ', $queryParams->order);

        if ($queryParams->limit) {
            $sentence .= ' LIMIT ' . $queryParams->limit;
            if ($queryParams->limitOffset) $sentence .= ' OFFSET ' . $queryParams->limitOffset;
        }

        if ($queryParams->forUpdate)
            $sentence .= ' FOR UPDATE';

        return $sentence;
    }

    public function insert(Query $query): string {
        $queryParams = $query->getParams();
        $input_parameters = [];
        $bind_parameters  = [];
        $keys_parameters  = [];
        foreach ($queryParams->data as $key => $value) {
            $bindKey = ":insk" . str_replace('.', '', $key);
            $keys_parameters[] = $key;
            if ($value instanceof Closure) {
                list($newK, $newV) = $value($bindKey);
                $bind_parameters[] = $newK;
                $input_parameters[$bindKey] = $newV;
            } else {
                $bind_parameters[] = $bindKey;
                $input_parameters[$bindKey] = $value;
            }
        }

        $sentence = 'INSERT INTO ' . $queryParams->table . ' (' . implode(',', $keys_parameters) . ') VALUES (' . implode(',', $bind_parameters) . ')';

        //--On Duplicate key
        if ($queryParams->onDuplicateKey) {
            $bindKeyValue = [];
            foreach ($queryParams->onDuplicateKey as $key => $value) {
                $bindKey = ":dpkeyup" . str_replace('.', '', $key);
                if ($value instanceof Closure) {
                    list($newK, $newV) = $value($bindKey);
                    $bindKeyValue[] = "{$key} = {$newK}";
                    $input_parameters[$bindKey] = $newV;
                } else {
                    $bindKeyValue[] = "{$key} = {$bindKey}";
                    $input_parameters[$bindKey] = $value;
                }
            }
            $sentence .= ' ON DUPLICATE KEY UPDATE ' . implode(', ', $bindKeyValue);
        }

        $query->setSentenceData($input_parameters);

        return $sentence;
    }

    public function insertMult(Query $query): string {
        $queryParams = $query->getParams();

        $input_parameters = [];
        $bind_parameters  = [];
        $keys_parameters  = [];
        $interate         = 0;

        $firstKey = array_key_first($queryParams->data);
        if (!is_array($queryParams->data[$firstKey])) Application::ErrorReporting()->dispatch("Insert Multiple required matrix array!");
        $keys_parameters = array_keys($queryParams->data[$firstKey]);

        foreach ($queryParams->data as $line) {
            $interate++;
            $letBind = [];
            foreach ($line as $key => $value) {
                $bindKey = ":ikmult{$interate}" . str_replace('.', '', $key);
                if ($value instanceof Closure) {
                    list($newK, $newV) = $value($bindKey);
                    $letBind[] = $newK;
                    $input_parameters[$bindKey] = $newV;
                } else {
                    $letBind[] = $bindKey;
                    $input_parameters[$bindKey] = $value;
                }
            }
            $bind_parameters[] = '(' . implode(',', $letBind) . ')';
        }

        $sentence = 'INSERT INTO ' . $queryParams->table . ' (' . implode(',', $keys_parameters) . ') VALUES ' . implode(',', $bind_parameters);

        if ($queryParams->onDuplicateKey) {
            $onDuplicate = [];
            foreach ($keys_parameters as $value)
                $onDuplicate[] = "{$value}=VALUES({$value})";
            $sentence .= ' ON DUPLICATE KEY UPDATE ' . implode(', ', $onDuplicate);
        }

        $query->setSentenceData($input_parameters);
        return $sentence;
    }

    public function update(Query $query): string {
        $queryParams = $query->getParams();

        $input_parameters = [];
        $bindKeyValue     = [];
        foreach ($queryParams->data as $key => $value) {
            $bindKey = ":upk" . str_replace('.', '', $key);
            if ($value instanceof Closure) {
                list($newK, $newV) = $value($bindKey);
                $bindKeyValue[] = "{$key} = {$newK}";
                $input_parameters[$bindKey] = $newV;
            } else {
                $bindKeyValue[] = "{$key} = {$bindKey}";
                $input_parameters[$bindKey] = $value;
            }
        }

        $sentence = 'UPDATE ' . $queryParams->table;
        if (count($queryParams->join))
            foreach ($queryParams->join as $join)
                $sentence .= ' ' . strtoupper($join['type']) . ' JOIN ' . $join['table'] . ' ON ' . implode(' AND ', $join['on']);
        $sentence .= ' SET ' . implode(', ', $bindKeyValue);
        if (count($queryParams->where)) $sentence .= ' WHERE ' . implode(' AND ', $queryParams->where);

        $query->setSentenceData($input_parameters);
        return $sentence;
    }

    public function delete(Query $query): string {
        $queryParams = $query->getParams();
        $sentence = "DELETE FROM " . $queryParams->table;
        if (count($queryParams->join))
            foreach ($queryParams->join as $join)
                $sentence .= ' ' . strtoupper($join['type']) . ' JOIN ' . $join['table'] . ' ON ' . implode(' AND ', $join['on']);
        if (count($queryParams->where)) $sentence .= ' WHERE ' . implode(' AND ', $queryParams->where);
        return $sentence;
    }


    public function lineNumbers(Query $query): string {
        $queryParams = $query->getParams();
        $sentence  = "SELECT COUNT(0) AS pages FROM {$queryParams->table}";
        if (count($queryParams->join)) {
            foreach ($queryParams->join as $join) {
                $sentence .= ' ' . strtoupper($join['type']) . ' JOIN ' . $join['table'] . ' ON ' . implode(' AND ', $join['on']);
            }
        }
        if (count($queryParams->where)) $sentence .= ' WHERE ' . implode(' AND ', $queryParams->where);
        if ($queryParams->group) $sentence .= ' GROUP BY ' . $queryParams->group;
        return $sentence;
    }
}
