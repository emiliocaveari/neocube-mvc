<?php

namespace NeoCube\Db;

use NeoCube\Db\Query\BuilderInferface;
use NeoCube\Db\Query\MysqlBuilder;
use NeoCube\Db\Query\Query;
use PDOStatement;
use PDO;
use NeoCube\View;

class Mapper {

    protected $_table = '';
    protected $_pk    = 'id';

    protected $error     = '';
    protected $saveInfo  = '';

    protected ?PDO $Db;

    private ?Query $Query  = null;
    private ?BuilderInferface $Builder = null;

    private $errorCode = null;
    private $errorInfo = null;

    private $paginate   = null;
    private $bindValues = array();
    private $execute    = true;
    private $fetchStm   = null;
    private $fetchMode  = PDO::FETCH_ASSOC;
    private $fetchClass = 'stdClass';

    private $sentence;
    private $rowCount;

    public function __construct(string $table = '', string $pk = '') {
        if ($table and !$this->_table) {
            $this->_table = $table;
            $this->_pk    = $pk ?: 'id';
        }
        $this->Db = Connection::factory();
        $this->Query = new Query($this->_table);
        $this->Builder = new MysqlBuilder();
    }

    public function getError(): string {
        return $this->error;
    }

    public function saveInfo(): string {
        return $this->saveInfo;
    }

    public function getErrorInfo(): array|null {
        return $this->errorInfo;
    }

    public function getErrorCode() {
        return $this->errorCode;
    }

    public function getLastInsertId() {
        return $this->Db->lastInsertId();
    }

    public function setFetchMode(string $mode = 'array'): static {
        $this->fetchMode = match ($mode) {
            'array' => PDO::FETCH_ASSOC,
            'object', 'stdClass' => PDO::FETCH_OBJ,
            default  => PDO::FETCH_CLASS
        };
        $this->fetchClass = $mode;
        return $this;
    }

    private function selectFetchMode(&$stm): void {
        if (!$stm instanceof PDOStatement) return;
        if (in_array($this->fetchMode, [PDO::FETCH_ASSOC, PDO::FETCH_OBJ]))
            $stm->setFetchMode($this->fetchMode);
        else
            $stm->setFetchMode($this->fetchMode, $this->fetchClass);
    }

    public function beginTransaction() {
        return $this->Db->beginTransaction();
    }

    public function commit() {
        return $this->Db->commit();
    }

    public function rollback() {
        return $this->Db->rollback();
    }

    public function clear(string|array $type = ''): static {
        if (empty($type)) {
            $clear = ['data', 'cols', 'where', 'order', 'limit', 'group', 'join', 'forupdate', 'onduplicatekey', 'bind', 'bindvalues'];
        } else if (is_array($type)) {
            $clear = array_map('strtolower', $type);
        } else {
            $clear = (strpos($type, ',') === false) ? [strtolower($type)] : array_map('strtolower', explode(',', $type));
        }

        $this->Query->clear($clear);

        if (in_array('bind', $clear) or in_array('bindvalues', $clear))
            $this->bindValues = [];

        $this->sentence = null;
        $this->fetchStm = null;
        return $this;
    }

    public function isSetParam(string $type = ''): bool {
        switch (strtolower($type)) {
            case 'bind':
            case 'bindvalues':
                return !!count($this->bindValues);
            default:
                return $this->Query->isSetParam($type);
        }
    }

    public function getParam(string $type = ''): mixed {
        switch (strtolower($type)) {
            case 'table':
                return $this->_table;
            case 'pk':
                return $this->_pk;
            case 'bindvalues':
                return $this->bindValues;
            case 'paginate':
                return $this->paginate;
            default:
                return $this->Query->getParam($type);
        }
    }

    protected function setSentence($sql): static {
        $this->sentence = $sql;
        return $this;
    }

    public function getSentence(): string {
        return $this->sentence;
    }

    public function noExecute(): static {
        $this->execute = false;
        return $this;
    }

    protected function executeSentence(array $input_parameters = null): bool|PDOStatement {
        if (!$this->execute) {
            $this->execute = true;
            return false;
        }
        if (!empty($this->sentence)) {
            try {
                if ($query = $this->Db->prepare($this->sentence)) {

                    if ($this->bindValues) {
                        if ($input_parameters === null) foreach ($this->bindValues as $k => $v) $query->bindValue($k, $v);
                        else foreach ($this->bindValues as $k => $v) $input_parameters[$k] = $v;
                    }

                    if ($query->execute($input_parameters)) {
                        $this->rowCount = $query->rowCount();
                    } else {
                        $this->errorCode = $query->errorCode();
                        $this->errorInfo = $query->errorInfo();
                        return false;
                    }
                } else {
                    $this->errorCode = $this->Db->errorCode();
                    $this->errorInfo = $this->Db->errorInfo();
                    return false;
                }
                return $query;
            } catch (\PDOException $e) {
                $this->errorCode = $e->getCode();
                $this->errorInfo = $e->errorInfo;
                return false;
            }
        } else {
            return false;
        }
    }

    public function rowCount(): int {
        return $this->rowCount;
    }

    public function setCols(string|array $cols): static {
        $this->Query->setCols($cols);
        return $this;
    }

    public function setCase(string $col, array $arrKeyVal, string $col_out, string $default = ''): static {
        $case = "CASE {$col} ";
        foreach ($arrKeyVal as $k => $v)
            $case .= " WHEN '{$k}' THEN '{$v}' ";
        if ($default) $case .= "ELSE '{$default}' ";
        $case .= "END AS {$col_out}";

        $this->Query->setCols($case);
        return $this;
    }

    public function setWhere(string $cond, array $bindValues = []): static {
        $this->Query->setWhere($cond);
        if ($bindValues) $this->setBindValues($bindValues);
        return $this;
    }

    public function setBindValues(array $bindValues = []): static {
        foreach ($bindValues as $key => $value) {
            if (strpos($key, ':') !== 0) $key = ":$key";
            $this->bindValues[$key] = $value;
        }
        return $this;
    }

    public function setOrder(string $order): static {
        $this->Query->setOrder($order);
        return $this;
    }

    public function setLimit(int $limit, int $offset = 0): static {
        $this->Query->setLimit($limit, $offset);
        return $this;
    }

    public function setGroup(string $group, string $having = ''): static {
        $this->Query->setGroup($group, $having);
        return $this;
    }

    public function setForUpdate(bool $bool): static {
        $this->Query->setForUpdate($bool);
        return $this;
    }

    public function setJoin(string|Mapper $table, string $on, string $type = ''): static {
        $where = [];
        if ($table instanceof Mapper) {
            $where = (array) $table->getParam('where');
            $bind  = (array) $table->getParam('bindvalues');
            $table = (string) $table->getParam('table');

            if ($bind) $this->setBindValues($bind);
        }
        $on = array_merge([$on], $where);
        $this->Query->setJoin($table, $on, $type);
        return $this;
    }

    public function onDuplicateKey(array $data): static {
        $this->Query->onDuplicateKey($data);
        return $this;
    }

    public function insertMult(array $data, bool $upDuplicateKeys = false): bool {
        $this->Query->setData($data)->onDuplicateKey($upDuplicateKeys);
        $this->sentence = $this->Builder->insertMult($this->Query);
        $input_parameters = $this->Query->getSentenceData();
        return ($this->executeSentence($input_parameters)) ? true : false;
    }

    public function insert(array $data): bool|int {
        $this->Query->setData($data);
        $this->sentence = $this->Builder->insert($this->Query);
        $input_parameters = $this->Query->getSentenceData();
        if ($this->executeSentence($input_parameters)) {
            $lastId = $this->Db->lastInsertId();
            return ($lastId !== false) ? $lastId : true;
        } else return false;
    }

    public function update(array $data): bool {
        $this->Query->setData($data);
        $this->sentence = $this->Builder->update($this->Query);
        $input_parameters = $this->Query->getSentenceData();
        return ($this->executeSentence($input_parameters)) ? true : false;
    }


    public function save(array $data): int|false {
        if (isset($data[$this->_pk])) {
            if (!empty($data[$this->_pk])) $id = $data[$this->_pk];
            unset($data[$this->_pk]);
        }
        if (isset($id)) {
            $this->clear()->setBindValues([':PK_MAPPER_ID' => $id]);
            $this->Query->setWhere("$this->_table.$this->_pk=:PK_MAPPER_ID");
            if (!$this->update($data)) $id = false;
            $this->saveInfo = 'UPDATE';
        } else {
            $id = $this->insert($data);
            $this->saveInfo = 'INSERT';
        }
        return $id;
    }

    public function paginate(?int $current = 1, int $numpages = 10, View|null &$view = null): static {
        $this->paginate = ['current' => ($current ?: 1), 'numpages' => $numpages, 'pages' => 0, 'view' => $view];
        if (is_numeric($current)) {
            $p = ($current - 1) * $numpages;
            if ($p < 0) $p = 0;
            $this->setLimit($numpages, $p);
        } else {
            $this->setLimit($numpages);
        }
        return $this;
    }

    public function fetchAll(): bool|array {
        if (empty($this->sentence))
            $this->sentence  = $this->Builder->select($this->Query);

        if (!is_null($this->paginate)) {
            $this->paginate['pages'] = ceil($this->lineNumbers() / $this->paginate['numpages']);
            if ($this->paginate['view'] instanceof View)
                $this->paginate['view']->setData(array_intersect_key($this->paginate, array_flip(['numpages', 'pages', 'current'])));
        }

        $stm = $this->executeSentence();
        if ($stm === false) return false;

        $this->selectFetchMode($stm);
        return $stm->fetchAll();
    }


    public function fetch(): bool|array|object {
        if (empty($this->sentence) or is_null($this->fetchStm)) {
            $this->sentence  = $this->Builder->select($this->Query);
            $this->fetchStm = $this->executeSentence();
            $this->selectFetchMode($this->fetchStm);
        }
        if ($this->fetchStm !== false)
            return $this->fetchStm->fetch();
        else
            return false;
    }


    public function find(string $id = ''): bool|array|object {
        if (empty($this->sentence)) {
            if (!empty($id)) {
                $this->Query->setWhere($this->_table . '.' . $this->_pk . '=:findprimarykey');
                $this->setBindValues([':findprimarykey' => $id]);
            }
            $this->sentence  = $this->Builder->select($this->Query);
        }

        $stm = $this->executeSentence();
        if ($stm === false) return false;

        $this->selectFetchMode($stm);
        return $stm->rowCount() ? $stm->fetch() : false;
    }

    public function lineNumbers(): int {
        $sentence  = $this->Builder->lineNumbers($this->Query);
        if ($query = $this->Db->prepare($sentence)) {
            if ($this->bindValues)
                foreach ($this->bindValues as $k => $v)
                    if (strpos($sentence, $k) !== false)
                        $query->bindValue($k, $v);
            if ($query->execute()) {
                $fetch = $query->fetch(PDO::FETCH_ASSOC);
                if ($fetch === false) return 0;
                $rowcount = $query->rowCount();
                return ($rowcount > 1) ? intval($rowcount) : intval($fetch['pages']);
            }
        }
        return 0;
    }

    public function delete(string $pk_value = ''): bool {
        if ($pk_value) {
            $this->Query->setWhere($this->_table . '.' . $this->_pk . '=:deleteprimarykey');
            $this->setBindValues([':deleteprimarykey' => $pk_value]);
        }
        $this->sentence  = $this->Builder->delete($this->Query);
        return $this->executeSentence() ? true : false;
    }

    public function toSelect(string|array $option, string $key = '', array|string|bool $params = false): array {
        if (!is_array($params)) {
            $params = [
                'separator' => '-',
                'empty'     => $params,
                'attr'      => [],
                'optgroup'  => [],
                'select'    => []
            ];
        } else {
            $params = array_merge(
                [
                    'separator' => '-',
                    'empty'     => false,
                    'attr'      => [],
                    'optgroup'  => [],
                    'select'    => []
                ],
                $params
            );
        }

        $select = is_array($params['select']) ? $params['select'] : [];
        $cols   = [];

        if ($params['empty'] !== false)
            $select = array_merge(['empty' => ($params['empty'] === true ? '' : $params['empty'])], $select);

        if (empty($key))
            $key = "{$this->_table}.{$this->_pk}";

        $cols[] = $key;

        if (is_array($option)) {
            foreach ($option as $col)
                if (!in_array($col, $cols))
                    $cols[] = $col;
        } else if (!in_array($option, $cols)) {
            $cols[] = $option;
        }

        if ($params['attr'] and is_array($params['attr'])) {
            foreach ($params['attr'] as $col)
                if (!in_array($col, $cols))
                    $cols[] = $col;
        } else unset($params['attr']);

        if (isset($params['optgroup']['label'])) {

            $label = $params['optgroup']['label'];

            if (is_array($label)) {
                foreach ($label as $col)
                    if (!in_array($col, $cols))
                        $cols[] = $col;
            } else if (!in_array($label, $cols)) {
                $cols[] = $label;
            }

            if (isset($params['optgroup']['attr']) and is_array($params['optgroup']['attr'])) {
                foreach ($params['optgroup']['attr'] as $col)
                    if (!in_array($col, $cols))
                        $cols[] = $col;
            } else unset($params['optgroup']['attr']);
        }

        $dados = $this->setCols($cols)->fetchAll();

        if ($dados) {
            $key = $this->Query::formatCol($key);
            foreach ($dados as $value) {
                $key_value = $value[$key];
                if (is_array($option)) {
                    $option_desc = array();
                    foreach ($option as $c) {
                        $option_desc[] = $value[$this->Query::formatCol($c)];
                    }
                    $letselect['option'] = implode($params['separator'], $option_desc);
                } else {
                    $letselect['option'] = $value[$this->Query::formatCol($option)];
                }

                if (isset($params['attr'])) {
                    foreach ($params['attr'] as $k => $c)
                        $letselect[$k] = $value[$this->Query::formatCol($c)];
                }

                if (isset($params['optgroup']['label'])) {
                    $label = $params['optgroup']['label'];
                    if (is_array($label)) {
                        $option_desc = array();
                        foreach ($label as $c)
                            $option_desc[] = $value[$this->Query::formatCol($c)];
                        $label = implode($params['separator'], $option_desc);
                    } else {
                        $label = $value[$this->Query::formatCol($label)];
                    }

                    if (isset($params['optgroup']['attr']) and is_array($params['optgroup']['attr'])) {
                        foreach ($params['optgroup']['attr'] as $k => $c)
                            $select['optgroup'][$label]['attr'][$k] = $value[$this->Query::formatCol($c)];
                        $select['optgroup'][$label]['value'][$key_value] = $letselect;
                    } else {
                        $select['optgroup'][$label][$key_value] = $letselect;
                    }
                } else $select[$key_value] = $letselect;
            }
        }
        return $select;
    }
}
