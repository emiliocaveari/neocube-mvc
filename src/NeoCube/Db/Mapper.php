<?php

namespace NeoCube\Db;

use PDOStatement;
use PDO;
use NeoCube\View;

class Mapper
{

    protected $_table = '';
    protected $_pk    = 'id';

    protected $db;

    protected $error     = '';
    protected $saveInfo  = '';

    protected $specialKeys = [];

    private $cols           = array();
    private $where          = array();
    private $order          = null;
    private $limit          = null;
    private $group          = null;
    private $join           = array();
    private $forupdate      = false;
    private $onDuplicateKey = array();
    private $paginate       = null;

    private $errorCode = null;
    private $errorInfo = null;


    private $bindValues = array();
    private $sentence;
    private $rowCount;
    private $execute   = true;
    private $fetchStm  = null;
    private $fetchMode = PDO::FETCH_ASSOC;
    private $fetchClass = 'stdClass';


    public function __construct(string $table = '', string $pk = '')
    {
        if ($table and !$this->_table) {
            $this->_table = $table;
            $this->_pk    = $pk ?: 'id';
        }
        $this->db = Connection::factory();
    }

    /**
     * Retorna erro
     * @return $this->error
     */
    public function getError(): string
    {
        return $this->error;
    }

    public function saveInfo(): string
    {
        return $this->saveInfo;
    }

    /**
     * Retorna erro
     * @return $this->errorInfo
     */
    public function getErrorInfo(): array|null
    {
        return $this->errorInfo;
    }

    /**
     * Retorna erro
     * @return $this->errorInfo
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * Retorna o ultimo ID inserido
     * @return PDO->lastInsertId
     */
    public function getLastInsertId()
    {
        return $this->db->lastInsertId();
    }


    public function setFetchMode(string $mode = 'array'): static
    {
        $this->fetchMode = match ($mode) {
            'array' => PDO::FETCH_ASSOC,
            'object', 'stdClass' => PDO::FETCH_OBJ,
            default  => PDO::FETCH_CLASS
        };
        $this->fetchClass = $mode;
        return $this;
    }

    private function selectFetchMode(&$stm): void
    {
        if (!$stm instanceof PDOStatement) return;
        if (in_array($this->fetchMode, [PDO::FETCH_ASSOC, PDO::FETCH_OBJ]))
            $stm->setFetchMode($this->fetchMode);
        else
            $stm->setFetchMode($this->fetchMode, $this->fetchClass);
    }
    /**
     * Campos a serem retornados
     * Padrão *
     * Aceita String ou Array
     *
     * @param $cols
     * @return this
     */
    public function setCols(string|array $col): static
    {
        if (!is_array($col)) {
            if (strpos($col, ',') !== false) $col = array($col);
            else                          $col = explode(',', $col);
        }
        foreach ($col as $c) {
            $query = $c;
            static::formatCol($c);
            if (!isset($this->cols[$c])) $this->cols[$c] = $query;
        }
        return $this;
    }


    /**
     * Implementa um retorno CASE do MySQL com os dados informados
     * @return this
     * @param $col - coluna a ser interpretada
     * @param $arrKeyVal - array do tipo [chave => valor]
     * @param $col_out - coluna retornada no SQL (situacao_desc)
     * @param $default - valor retornado como padrão
     */
    public function setCase(string $col, array $arrKeyVal, string $col_out, string $default = ''): static
    {
        $case = "CASE {$col} ";
        foreach ($arrKeyVal as $k => $v)
            $case .= " WHEN '{$k}' THEN '{$v}' ";
        if ($default) $case .= "ELSE '{$default}' ";
        $case .= "END AS {$col_out}";

        $this->setCols($case);
        return $this;
    }

    /**
     * WHERE
     *
     * @param string $cond
     * @param string $fetch AND,OR
     * @return this
     */
    public function setWhere(string $cond, array $bindValues = []): static
    {
        $this->where[] = $cond;
        if ($bindValues) $this->setBindValues($bindValues);

        return $this;
    }

    /**
     * BIND VALUE PDO
     *
     * @param array $bindValues [:value => value]
     * @return this
     */
    public function setBindValues(array $bindValues = []): static
    {
        foreach ($bindValues as $key => $value) {
            if (strpos($key, ':') !== 0) $key = ":$key";
            $this->bindValues[$key] = $value;
        }
        return $this;
    }

    /**
     * ORDER BY
     *
     * @param string $order ORDER BY $order
     * @return this
     */
    public function setOrder(string $order): static
    {
        if (is_null($this->order)) $this->order = ' ORDER BY ';
        else $this->order .= ', ';

        $this->order .= $order;
        return $this;
    }

    /**
     * LIMIT
     *
     * @param string $limite
     * @param string $inicio  default = 0
     * @return this
     */
    public function setLimit(int $limit, int $offset = 0): static
    {
        $this->limit = ' LIMIT ' . $limit;
        if ($offset > 0) $this->limit .= ' OFFSET ' . $offset;
        return $this;
    }

    /**
     * GROUP BY
     *
     * @param string $val  GROUP BY $val
     * @return this
     */
    public function setGroup(string $val, string $having = ''): static
    {
        $this->group = !empty($val) ?
            ' GROUP BY ' . $val . ($having ? " HAVING {$having}" : '')
            : null;
        return $this;
    }



    /**
     * FOR UPDATE
     *
     * @param boolean $bool
     * @return this
     */
    public function setForUpdate(bool $bool): static
    {
        $this->forupdate = $bool;
        return $this;
    }


    /**
     * JOIN
     *
     * @param string|Mapper $table
     * @param string $on JOIN $table ON $on;
     * @param string $type LEFT,RIGHT,OUTER,INNER,LEFT OUTER,RIGHT OUTER;
     * @return this
     */
    public function setJoin(string|Mapper $table, string $on, string $type = ''): static
    {

        if ($table instanceof Mapper) {
            $tab   = $table->getParam('table');
            $where = (array) $table->getParam('where');

            $bind  = (array) $table->getParam('bindvalues');
            if ($bind) $this->setBindValues($bind);

            if ($type) $join = ' ' . strtoupper($type) . ' JOIN ' . $tab . ' ON ';
            else         $join = ' JOIN ' . $tab . ' ON ';

            if ($on)    $join .= $on;
            if ($where) $join .= ($on ? ' AND ' : '') . implode(' AND ', $where);

            $this->join[$tab] = $join;
        } else {
            if ($type) $join = ' ' . strtoupper($type) . ' JOIN ' . $table;
            else         $join = ' JOIN ' . $table;

            if ($on) $join .= ' ON ' . $on;

            $this->join[$table] = $join;
        }
        return $this;
    }

    /**
     * ON DUPLICATE KEY UPDATE , setWhere begins
     *
     * @param array $data array('campo'=>'value')
     */
    public function onDuplicateKey(array $data): static
    {
        foreach ($data as $key => $val) {
            if ($val === 0 or $val === '0' or !empty($val) or is_null($val)) {
                $this->onDuplicateKey[$key] = $val;
            }
        }
        return $this;
    }


    /**
     * Inicia uma transação
     *
     * @return true or false
     */
    public function beginTransaction()
    {
        return $this->db->beginTransaction();
    }

    /**
     * Salva transação
     *
     * @return true or false
     */
    public function commit()
    {
        return $this->db->commit();
    }

    /**
     * Desfaz tranzação transação
     *
     * @return true or false
     */
    public function rollback()
    {
        return $this->db->rollback();
    }


    /**
     * Apaga dados setados
     *
     * @param string $type where,cols,order,limit,group,join
     * @param array $type where,cols,order,limit,group,join
     * @return this
     */
    public function clear(string|array $type = ''): static
    {

        if (is_array($type)) {
            foreach ($type as $value) $this->clear($value);
        } else {
            switch (strtolower($type)) {
                case 'cols':
                    $this->cols  = array();
                    break;
                case 'where':
                    $this->where = array();
                    break;
                case 'order':
                    $this->order = null;
                    break;
                case 'limit':
                    $this->limit = null;
                    break;
                case 'group':
                    $this->group = null;
                    break;
                case 'join':
                    $this->join  = [];
                    break;
                case 'paginate':
                    $this->paginate  = null;
                    break;
                case 'onduplicatekey':
                    $this->onDuplicateKey = [];
                    break;
                case 'bind':
                case 'bindvalues':
                    $this->bindValues = [];
                    break;
                default:
                    $this->cols  = array();
                    $this->where = array();
                    $this->order = null;
                    $this->limit = null;
                    $this->group = null;
                    $this->join  = [];
                    $this->paginate = null;
                    $this->onDuplicateKey = [];
                    $this->bindValues = [];
                    break;
            }
            $this->sentence = null;
            $this->fetchStm = null;
        }
        return $this;
    }

    /**
     * Verifica se parametro foi setado
     *
     * @param string $type where,cols,order,limit,group,join
     * @return BOLLEAN
     */
    public function isSetParam(string $type = ''): bool
    {
        switch (strtolower($type)) {
            case 'cols':
                return !!count($this->cols);
            case 'where':
                return !!count($this->where);
            case 'order':
                return !is_null($this->order);
            case 'limit':
                return !is_null($this->limit);
            case 'group':
                return !is_null($this->group);
            case 'join':
                return !!count($this->join);
            case 'bindvalues':
                return !!count($this->bindValues);
            default:
                return false;
        }
    }

    /**
     * Retorna parametro setado
     *
     * @param string $type where,cols,order,limit,group,join
     * @return param
     */
    public function getParam(string $type = ''): mixed
    {
        switch (strtolower($type)) {
            case 'table':
                return $this->_table;
            case 'pk':
                return $this->_pk;
            case 'cols':
                return $this->cols;
            case 'where':
                return $this->where;
            case 'order':
                return $this->order;
            case 'limit':
                return $this->limit;
            case 'group':
                return $this->group;
            case 'join':
                return $this->join;
            case 'bindvalues':
                return $this->bindValues;
            default:
                return null;
        }
    }


    /**
     * Seta sentença SQL
     * @param string $sql
     * @return null
     */
    protected function setSentence($sql): static
    {
        $this->sentence = $sql;
        return $this;
    }


    /**
     * Retorna sentença executada
     *
     * @return string
     */
    public function getSentence(): string
    {
        return $this->sentence;
    }


    /**
     * Não executa sentença
     * Usado para retornar o SQL gerado antes de executar
     *
     * @return static
     */
    public function noExecute(): static
    {
        $this->execute = false;
        return $this;
    }

    /**
     * Executa sentença SQL
     *
     * @return PDOStatement or false
     */
    protected function executeSentence(array $input_parameters = null): bool|PDOStatement
    {

        if (!$this->execute) {
            $this->execute = true;
            return false;
        }
        if (!empty($this->sentence)) {
            try {
                if ($query = $this->db->prepare($this->sentence)) {

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
                    $this->errorCode = $this->db->errorCode();
                    $this->errorInfo = $this->db->errorInfo();
                    return false;
                }
                return $query;
            } catch (\PDOThrowable $e) {
                $this->errorCode = $e->getCode();
                $this->errorInfo = $e->errorInfo;
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Retorna numero de linhas afetados na query
     *
     * @return int
     */
    public function rowCount(): int
    {
        return $this->rowCount;
    }





    /**
     * INSERT MULTIPLAS LINHAS
     *
     * @param array $data array(0=>array('key'=>'value'),1=>array('key'=>'value2'))
     * @return true caso sucesso, caso contrario false
     */
    public function insertMult(array $data, $upDuplicateKeys = false): bool
    {

        $bind_parameters  = [];
        $keys_parameters  = [];
        $input_parameters = [];
        $interate         = 0;

        foreach ($data as $line) {
            $interate++;
            if (!is_array($line)) return false;
            //--Keys table
            if (!count($keys_parameters)) $keys_parameters = array_keys($line);
            //--Binde values local
            $letBind = [];
            foreach ($line as $key => $val) {
                $bindKey = ":ikmult{$interate}" . str_replace('.', '', $key);

                if (isset($this->specialKeys[$key]))
                    $letBind[] = str_replace(':skvalue', $bindKey, $this->specialKeys[$key]);
                else
                    $letBind[] = $bindKey;

                $input_parameters[$bindKey] = $val;
            }
            //--Binde values to sentence
            $bind_parameters[] = '(' . implode(',', $letBind) . ')';
        }
        $this->sentence = 'INSERT INTO ' . $this->_table . ' (' . implode(',', $keys_parameters) . ') VALUES ' . implode(',', $bind_parameters);

        //--Atualizar caso keys já existam
        if ($upDuplicateKeys) {
            $onDuplicate = [];
            foreach ($keys_parameters as $value) {
                $onDuplicate[] = "{$value}=VALUES({$value})";
            }
            $this->sentence .= ' ON DUPLICATE KEY UPDATE ' . implode(', ', $onDuplicate);
        }

        if ($this->executeSentence($input_parameters)) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * INSERT
     *
     * @param array $data array('campo'=>'value')
     * @return lastInsertId caso sucesso, caso contrario false
     */
    public function insert(array $data): bool|int
    {

        $bind_parameters  = [];
        $keys_parameters  = [];
        $input_parameters = [];
        foreach ($data as $key => $val) {
            $bindKey = ":insk" . str_replace('.', '', $key);

            if (isset($this->specialKeys[$key]))
                $bind_parameters[] = str_replace(':skvalue', $bindKey, $this->specialKeys[$key]);
            else
                $bind_parameters[] = $bindKey;

            $keys_parameters[] = $key;
            $input_parameters[$bindKey] = $val;
        }

        $this->sentence = 'INSERT INTO ' . $this->_table . ' (' . implode(',', $keys_parameters) . ') VALUES (' . implode(',', $bind_parameters) . ')';

        //--On Duplicate key
        if (count($this->onDuplicateKey)) {
            $bindKeyValue = [];
            foreach ($this->onDuplicateKey as $key => $val) {
                $bindKey = ":dpkeyup" . str_replace('.', '', $key);
                if (isset($this->specialKeys[$key]))
                    $bindKeyValue[] = "{$key} = " . str_replace(':skvalue', $bindKey, $this->specialKeys[$key]);
                else
                    $bindKeyValue[] = "{$key} = {$bindKey}";
                $input_parameters[$bindKey] = $val;
            }
            $this->sentence .= ' ON DUPLICATE KEY UPDATE ' . implode(', ', $bindKeyValue);
        }

        if ($this->executeSentence($input_parameters)) {
            $lastId = $this->db->lastInsertId();
            return ($lastId) ? $lastId : true;
        } else {
            return false;
        }
    }


    /**
     * UPDATE , setWhere begins
     *
     * @param array $data array('campo'=>'value')
     * @return boolean
     */
    public function update(array $data): bool
    {
        $input_parameters = [];
        $bindKeyValue     = [];
        foreach ($data as $key => $val) {
            $bindKey = ":upk" . str_replace('.', '', $key);
            if (isset($this->specialKeys[$key]))
                $bindKeyValue[] = "{$key} = " . str_replace(':skvalue', $bindKey, $this->specialKeys[$key]);
            else
                $bindKeyValue[] = "{$key} = {$bindKey}";
            $input_parameters[$bindKey] = $val;
        }
        $this->sentence = 'UPDATE ' . $this->_table .
            ' ' . implode(' ', $this->join) .
            ' SET ' . implode(', ', $bindKeyValue) .
            ($this->where ? ' WHERE ' . implode(' AND ', $this->where) : '');

        return ($this->executeSentence($input_parameters)) ? true : false;
    }


    public function save(array $data): int|false
    {
        //--verificando id
        if (isset($data[$this->_pk])) {
            if (!empty($data[$this->_pk])) $id = $data[$this->_pk];
            unset($data[$this->_pk]);
        }
        //--Salvando dados
        if (isset($id)) {
            if (!$this->clear()->setWhere("$this->_table.$this->_pk=:PK_MAPPER_ID", [':PK_MAPPER_ID' => $id])->update($data))
                $id = false;
            $this->saveInfo = 'UPDATE';
        } else {
            $id = $this->insert($data);
            $this->saveInfo = 'INSERT';
        }
        return $id;
    }



    public function paginate(?int $current = 1, int $numpages = 10, View|null &$view = null): static
    {
        $this->paginate = ['current' => ($current ?: 1), 'numpages' => $numpages, 'view' => $view];
        if (is_numeric($current)) {
            $p = ($current - 1) * $numpages;
            if ($p < 0) $p = 0;
            $this->setLimit($numpages, $p);
        } else {
            $this->setLimit($numpages);
        }
        return $this;
    }



    public function fetchAll(): bool|array
    {

        //--Monta Sentence
        if (empty($this->sentence)) {
            $this->sentence  = 'SELECT ';
            $this->sentence .= (count($this->cols)) ? implode(',', $this->cols) : '*';
            $this->sentence .= ' FROM ' . $this->_table;
            if (count($this->join)) $this->sentence .= implode(' ', $this->join);
            if (count($this->where)) $this->sentence .= ' WHERE ' . implode(' AND ', $this->where) . ' ';
            if (!is_null($this->group)) $this->sentence .= $this->group;
            if (!is_null($this->order)) $this->sentence .= $this->order;
            if (!is_null($this->limit)) $this->sentence .= $this->limit;
            if ($this->forupdate)        $this->sentence .= ' FOR UPDATE';
        }

        //--Dados de paginação para view
        if (!is_null($this->paginate) and $this->paginate['view'] instanceof View) {
            $pages = ceil($this->lineNumbers() / $this->paginate['numpages']);
            $this->paginate['view']->setData(array('current' => $this->paginate['current'], 'pages' => $pages));
        }

        //--Retornando dados
        $stm = $this->executeSentence();
        if ($stm === false) return false;

        $this->selectFetchMode($stm);
        return $stm->fetchAll();
    }


    public function fetch(): bool|array|object
    {
        //--Monta Sentence
        if (empty($this->sentence) or is_null($this->fetchStm)) {
            $this->sentence  = 'SELECT ';
            $this->sentence .= (count($this->cols)) ? implode(',', $this->cols) : '*';
            $this->sentence .= ' FROM ' . $this->_table;
            if (count($this->join)) $this->sentence .= implode(' ', $this->join);
            if (count($this->where)) $this->sentence .= ' WHERE ' . implode(' AND ', $this->where) . ' ';
            if (!is_null($this->group)) $this->sentence .= $this->group;
            if (!is_null($this->order)) $this->sentence .= $this->order;
            if (!is_null($this->limit)) $this->sentence .= $this->limit;
            if ($this->forupdate)        $this->sentence .= ' FOR UPDATE';
            $this->fetchStm = $this->executeSentence();
            $this->selectFetchMode($this->fetchStm);
        }

        if ($this->fetchStm !== false)
            return $this->fetchStm->fetch();
        else
            return false;
    }


    public function find(string $id = ''): bool|array|object
    {

        if (empty($this->sentence)) {
            if (!empty($id)) $this->setWhere($this->_table . '.' . $this->_pk . '=:findprimarykey')->setBindValues([':findprimarykey' => $id]);

            $this->sentence  = 'SELECT ';
            $this->sentence .= (count($this->cols)) ? implode(',', $this->cols) : '*';
            $this->sentence .= ' FROM ' . $this->_table;

            if (count($this->join)) $this->sentence .= implode(' ', $this->join);
            if (count($this->where)) $this->sentence .= ' WHERE ' . implode(' AND ', $this->where) . ' ';
            if (!is_null($this->group)) $this->sentence .= $this->group;
            if (!is_null($this->order)) $this->sentence .= $this->order;
            if ($this->forupdate)      $this->sentence .= ' FOR UPDATE';
        }

        $stm = $this->executeSentence();
        if ($stm === false) return false;

        $this->selectFetchMode($stm);
        return $stm->rowCount() ? $stm->fetch() : false;
    }

    /**
     * Retorna numero de linhas
     *
     * @return integer
     */
    public function lineNumbers(): int
    {
        //--Local sentence
        $sentence  = "SELECT COUNT(0) AS paginas FROM {$this->_table}";
        if (count($this->join)) $sentence .= implode(' ', $this->join);
        if (count($this->where)) $sentence .= ' WHERE ' . implode(' AND ', $this->where) . ' ';
        if (!is_null($this->group)) $sentence .= $this->group;
        //--Executa Nova sentenca
        if ($query = $this->db->prepare($sentence)) {
            if ($this->bindValues)
                foreach ($this->bindValues as $k => $v)
                    if (strpos($sentence, $k) !== false)
                        $query->bindValue($k, $v);
            if ($query->execute()) {
                $fetch = $query->fetch(PDO::FETCH_ASSOC);
                if ($fetch === false) return 0;
                $rowcount = $query->rowCount();
                return ($rowcount > 1) ? intval($rowcount) : intval($fetch['paginas']);
            }
        }
        return 0;
    }



    /**
     * DELETE, setWhere begins
     *
     * @return boolean
     */
    public function delete(string $id = ''): bool
    {
        if ($id) $this->setWhere($this->_table . '.' . $this->_pk . '=:deleteprimarykey', [':deleteprimarykey' => $id]);
        $this->sentence = "DELETE FROM " . $this->_table . ($this->where ? ' WHERE ' . implode(' AND ', $this->where) : '');
        return $this->executeSentence() ? true : false;
    }

    /**
     * DELETE, setWhere begins
     *
     * @return boolean
     */
    public function joinDelete(string $table = ''): bool
    {
        $this->sentence = 'DELETE ' . $table . ' FROM ' . $this->_table;
        if (count($this->join)) $this->sentence .= implode(' ', $this->join);
        if (count($this->where)) $this->sentence .= ' WHERE ' . implode(' AND ', $this->where);
        return $this->executeSentence() ? true : false;
    }




    public function toSelect(string|array $option, string $key = '', array|string|bool $params = false): array
    {

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

        if (empty($key)) $key = "{$this->_table}.{$this->_pk}";
        $cols[] = $key;

        //--Selecionando cols de option
        if (is_array($option)) {
            foreach ($option as $col)
                if (!in_array($col, $cols))
                    $cols[] = $col;
        } else if (!in_array($option, $cols)) {
            $cols[] = $option;
        }

        //--Selecionando atributos
        if ($params['attr'] and is_array($params['attr'])) {
            foreach ($params['attr'] as $col)
                if (!in_array($col, $cols))
                    $cols[] = $col;
        } else unset($params['attr']);


        //--Selecionando optgroup
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

        //--retornando do banco de dados
        $dados = $this->setCols($cols)->fetchAll();

        if ($dados) {

            static::formatCol($key);

            foreach ($dados as $value) {

                $key_value = $value[$key];

                //--option
                if (is_array($option)) {
                    $option_desc = array();
                    foreach ($option as $c) {
                        static::formatCol($c);
                        $option_desc[] = $value[$c];
                    }
                    $letselect['option'] = implode($params['separator'], $option_desc);
                } else {
                    static::formatCol($option);
                    $letselect['option'] = $value[$option];
                }

                if (isset($params['attr'])) {
                    foreach ($params['attr'] as $k => $c) {
                        static::formatCol($c);
                        $letselect[$k] = $value[$c];
                    }
                }

                //--optgroup
                if (isset($params['optgroup']['label'])) {

                    $label = $params['optgroup']['label'];

                    if (is_array($label)) {
                        $option_desc = array();
                        foreach ($label as $c) {
                            static::formatCol($c);
                            $option_desc[] = $value[$c];
                        }
                        $label = implode($params['separator'], $option_desc);
                    } else {
                        static::formatCol($label);
                        $label = $value[$label];
                    }

                    if (isset($params['optgroup']['attr'])) {
                        foreach ($params['optgroup']['attr'] as $k => $c) {
                            static::formatCol($c);
                            $select['optgroup'][$label]['attr'][$k] = $value[$c];
                        }
                        $select['optgroup'][$label]['value'][$key_value] = $letselect;
                    } else {
                        $select['optgroup'][$label][$key_value] = $letselect;
                    }
                } else $select[$key_value] = $letselect;
            }
        }
        return $select;
    }

    static private function formatCol(&$col): void
    {
        //--Remove table name
        $point = strrpos($col, '.');
        if ($point !== false) $col  = substr($col, $point + 1);
        //--Retorna Alias
        $as = strrpos(strtolower($col), ' as ');
        if ($as !== false) {
            $col = str_replace(' ', '', substr($col, $as + 3));
        }
    }
}
