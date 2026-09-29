<?php

namespace NeoCube\Db\Query;

use Closure;
use NeoCube\Application;

class PostgreBuilder implements BuilderInferface {

    public function select(Query $query): string {
        $queryParams = $query->getParams();

        $sentence  = 'SELECT ';
        $sentence .= (count($queryParams->cols)) ? implode(',', $queryParams->cols) : '*';
        $sentence .= ' FROM ' . $queryParams->table;
        if ($queryParams->alias) $sentence .= ' ' . $queryParams->alias;


        if (count($queryParams->join))
            foreach ($queryParams->join as $join) {
                $table = $join['table'];
                if (!empty($join['alias'])) $table .= ' ' . $join['alias'];
                $sentence .= ' ' . strtoupper($join['type']) . ' JOIN ' . $table . ' ON ' . implode(' AND ', $join['on']);
            }

        if (count($queryParams->where))
            $sentence .= ' WHERE ' . implode(' AND ', $queryParams->where);

        if ($queryParams->group) {
            $sentence .= ' GROUP BY ' . $queryParams->group;
            if ($queryParams->having) $sentence .= ' HAVING ' . $queryParams->having;
        }

        if (count($queryParams->order))
            $sentence .= ' ORDER BY ' . implode(', ', $queryParams->order);

        if ($queryParams->limit) {
            $sentence .= ' LIMIT ' . $queryParams->limit;
            if ($queryParams->offset) $sentence .= ' OFFSET ' . $queryParams->offset;
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
                $closureRes = $value($bindKey, $queryParams->data);
                if (is_array($closureRes)) {
                    list($newK, $newV) = $closureRes;
                    $bind_parameters[] = $newK;
                    $input_parameters[$bindKey] = $newV;
                } else {
                    $bind_parameters[] = $closureRes;
                }
            } else {
                $bind_parameters[] = $bindKey;
                $input_parameters[$bindKey] = $value;
            }
        }

        $sentence = 'INSERT INTO ' . $queryParams->table . ' (' . implode(',', $keys_parameters) . ') VALUES (' . implode(',', $bind_parameters) . ')';

        if ($queryParams->duplicateKey) {
            if (empty($queryParams->conflictColumns)) {
                Application::ErrorReporting()->dispatch("PostgreBuilder::insert() requer conflictColumns em setDuplicateKey() para montar o ON CONFLICT!");
            } else {
                $bindKeyValue = [];
                foreach ($queryParams->duplicateKey as $key => $value) {
                    $bindKey = ":dpkeyup" . str_replace('.', '', $key);
                    if ($value instanceof Closure) {
                        $closureRes = $value($bindKey, $queryParams->data);
                        if (is_array($closureRes)) {
                            list($newK, $newV) = $closureRes;
                            $bindKeyValue[] = "{$key} = {$newK}";
                            $input_parameters[$bindKey] = $newV;
                        } else {
                            $bindKeyValue[] = "{$key} = {$closureRes}";
                        }
                    } else {
                        $bindKeyValue[] = "{$key} = {$bindKey}";
                        $input_parameters[$bindKey] = $value;
                    }
                }
                $sentence .= ' ON CONFLICT (' . implode(',', $queryParams->conflictColumns) . ') DO UPDATE SET ' . implode(', ', $bindKeyValue);
            }
        }

        if (!empty($queryParams->returning))
            $sentence .= ' RETURNING ' . implode(',', $queryParams->returning);

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
                    $closureRes = $value($bindKey, $line);
                    if (is_array($closureRes)) {
                        list($newK, $newV) = $closureRes;
                        $letBind[] = $newK;
                        $input_parameters[$bindKey] = $newV;
                    } else {
                        $letBind[] = $closureRes;
                    }
                } else {
                    $letBind[] = $bindKey;
                    $input_parameters[$bindKey] = $value;
                }
            }
            $bind_parameters[] = '(' . implode(',', $letBind) . ')';
        }

        $sentence = 'INSERT INTO ' . $queryParams->table . ' (' . implode(',', $keys_parameters) . ') VALUES ' . implode(',', $bind_parameters);

        if ($queryParams->duplicateKey) {
            if (empty($queryParams->conflictColumns)) {
                Application::ErrorReporting()->dispatch("PostgreBuilder::insertMult() requer conflictColumns em setDuplicateKey() para montar o ON CONFLICT!");
            } else {
                $onConflict = [];
                foreach ($keys_parameters as $value)
                    $onConflict[] = "{$value} = EXCLUDED.{$value}";
                $sentence .= ' ON CONFLICT (' . implode(',', $queryParams->conflictColumns) . ') DO UPDATE SET ' . implode(', ', $onConflict);
            }
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
                $closureRes = $value($bindKey, $queryParams->data);
                if (is_array($closureRes)) {
                    list($newK, $newV) = $closureRes;
                    $bindKeyValue[] = "{$key} = {$newK}";
                    $input_parameters[$bindKey] = $newV;
                } else {
                    $bindKeyValue[] = "{$key} = {$closureRes}";
                }
            } else {
                $bindKeyValue[] = "{$key} = {$bindKey}";
                $input_parameters[$bindKey] = $value;
            }
        }

        $sentence = 'UPDATE ' . $queryParams->table;
        if ($queryParams->alias) $sentence .= ' ' . $queryParams->alias;
        $sentence .= ' SET ' . implode(', ', $bindKeyValue);

        //--Postgres não tem UPDATE...JOIN: os joins viram FROM + condições no WHERE.
        $whereConditions = [];
        if (count($queryParams->join)) {
            $fromTables = [];
            foreach ($queryParams->join as $join) {
                $table = $join['table'];
                if (!empty($join['alias'])) $table .= ' ' . $join['alias'];
                $fromTables[] = $table;
                $whereConditions = array_merge($whereConditions, $join['on']);
            }
            $sentence .= ' FROM ' . implode(', ', $fromTables);
        }
        $whereConditions = array_merge($whereConditions, $queryParams->where);

        if (count($whereConditions)) $sentence .= ' WHERE ' . implode(' AND ', $whereConditions);

        $query->setSentenceData($input_parameters);
        return $sentence;
    }

    public function delete(Query $query): string {
        $queryParams = $query->getParams();

        $sentence = 'DELETE FROM ' . $queryParams->table;
        if ($queryParams->alias) $sentence .= ' ' . $queryParams->alias;

        //--Postgres não tem DELETE...JOIN: os joins viram USING + condições no WHERE.
        $whereConditions = [];
        if (count($queryParams->join)) {
            $usingTables = [];
            foreach ($queryParams->join as $join) {
                $table = $join['table'];
                if (!empty($join['alias'])) $table .= ' ' . $join['alias'];
                $usingTables[] = $table;
                $whereConditions = array_merge($whereConditions, $join['on']);
            }
            $sentence .= ' USING ' . implode(', ', $usingTables);
        }
        $whereConditions = array_merge($whereConditions, $queryParams->where);

        if (count($whereConditions)) $sentence .= ' WHERE ' . implode(' AND ', $whereConditions);

        return $sentence;
    }


    public function lineNumbers(Query $query): string {
        $queryParams = $query->getParams();
        $sentence  = "SELECT COUNT(0) AS pages FROM {$queryParams->table}";
        if ($queryParams->alias) $sentence .= ' ' . $queryParams->alias;
        if (count($queryParams->join)) {
            foreach ($queryParams->join as $join) {
                $table = $join['table'];
                if (!empty($join['alias'])) $table .= ' ' . $join['alias'];
                $sentence .= ' ' . strtoupper($join['type']) . ' JOIN ' . $table . ' ON ' . implode(' AND ', $join['on']);
            }
        }
        if (count($queryParams->where)) $sentence .= ' WHERE ' . implode(' AND ', $queryParams->where);
        if ($queryParams->group) $sentence .= ' GROUP BY ' . $queryParams->group;
        return $sentence;
    }
}
