<?php
declare(strict_types=1);

namespace work\cor;

use work\Config;
use work\cor\anomaly\PdoCustomException;
use work\cor\pdo\PdoConnect;
use work\HelperFun;

/**
 * pdo调用
 * Class PdoQuery
 * @package work\cor
 */
class PdoQuery
{
    //前缀
    private string $prefix = '';

    //sql关键字
    private array $sql = [
        "fields" => '',
        "from" => 'FROM',
        'table' => '',
        "join" => '',
        "where" => '',
        "group" => '',
        'having' => '',
        "order" => '',
        "limit" => '',
        "lock" => '',
        "union" => ''
    ];
    private bool $outputParseSql = false;
    private array $unionWhere = [];
    private array $valueArr = [];
    private array $updateExp = [];
    private ?\Closure $filterFunction = null;
    private int $parse = 0;
    //解析绑定下标
    private int $parseAgr = 0;
    //记录运行日志
    private bool $recordLog = false;
    //日志实例
    protected \work\cor\Log $logObj;
    //pdo连接
    protected ?PdoConnect $pdoConn = null;
    //选择的key
    protected ?string $optionsDbKey = 'default';
    //查询请求
    protected bool $queryFlag = false;

    private bool $autoRetFlag = false;
    //sql值转义
    private bool $sqlEscape = true;


    public function __construct(?string $key = 'default', array $conf = [])
    {
        $this->autoRetFlag = (bool) Config::getInstance()->get("pdoDb.autoRet",false);
        if (!array_key_exists('prefix', $conf)) {
            $this->prefix = (string)Config::getInstance()->get('pdoDb.prefix', $this->prefix);
        } else {
            $this->prefix = $conf['prefix'];
        }
        if (!array_key_exists('recordLog', $conf)) {
            $this->recordLog = (bool)Config::getInstance()->get('pdoDb.recordLog', $this->recordLog);
        } else {
            $this->recordLog = (bool)$conf['recordLog'];
        }
        if (array_key_exists('sqlEscape',$conf)) {
            $this->sqlEscape = (bool) $conf['sqlEscape'];
        }

        $this->logObj = new \work\cor\Log();
        $this->optionsDbKey = $key;
    }


    /**
     * 设置pdo连接
     * @param PdoConnect $pdoCon
     * @throws PdoCustomException
     */
    public function setPdoConnect(PdoConnect $pdoCon)
    {
        if ($this->optionsDbKey !== null) {
            throw new PdoCustomException("db key not equal to NULL");
        }
        $this->pdoConn = $pdoCon;
    }

    /**
     * 获取连接key
     * @return PdoConnect|null
     * @throws PdoCustomException
     */
    public function getPdoConnectInfo(): ?PdoConnect
    {
        if ($this->pdoConn instanceof PdoConnect) {
            return $this->pdoConn;
        }
        if ($this->optionsDbKey === null) {
            throw new PdoCustomException("no set option db key");
        }
        $this->pdoConn = new PdoConnect($this->optionsDbKey);
        return $this->pdoConn;
    }

    /**
     * @param bool $autoFlag
     * @return $this
     */
    public function setAutoRet(bool $autoFlag) :self
    {
        $this->autoRetFlag = $autoFlag;
        return $this;
    }


    /**
     * 清空sql
     * @return void
     */
    public function cleanSql()
    {
        foreach ($this->sql as $key => &$value) {
            if ($key === 'from') {
                continue;
            }
            $value = '';
        }
        unset($value);
        $this->valueArr = [];
        $this->parseAgr = 0;
    }

    /**
     * 表名
     * @param string $table
     * @return $this
     */
    public function name(string $table = ''): self
    {
        $this->cleanSql();
        $this->sql['table'] = '`' . $this->prefix . $table . '`';
        return $this;
    }

    /**
     * 表名
     * @param string $table
     * @return $this
     */
    public function table(string $table = ''): self
    {
        $this->cleanSql();
        $this->sql['table'] = "`{$table}`";
        return $this;
    }

    /**
     * 表别名
     * @param string $str
     * @return $this
     */
    public function alias(string $str = ''): self
    {
        $this->sql['table'] .= " as {$str}";
        return $this;
    }

    //开启事务
    public function beginTransaction(): bool
    {
        return $this->getPdoConnectInfo()->beginTransaction();
    }

    //提交事务
    public function commit(): bool
    {
        $res = $this->getPdoConnectInfo()->commit();
        $this->doneRun();
        return $res;
    }

    //事务回滚
    public function rollback(): bool
    {
        $res =  $this->getPdoConnectInfo()->rollback();
        $this->doneRun();
        return $res;
    }

    /**
     * where条件支持 'id','=',1或['id' => 1]或[['id','=',1]]
     * 支持in条件只能在一个字段上用
     * 支持between
     */
    public function where($param = '', string $symbol = '', $val = ''): self
    {
        if (empty($param) || (is_string($param) && empty($val) && !is_numeric($val))) {
            return $this;
        }
        $this->sql['where'] .= empty($this->sql['where']) ? 'WHERE' : ' AND';
        if (is_string($param)) {
            $paramArr = $this->analysis($param);
            $symbol = strtoupper($symbol);
            if (in_array($symbol, ['IN', 'BETWEEN', 'NOT IN'], true)) {
                $info = $this->otherWhere($paramArr['field'], $symbol, $val);
                if (empty($info)) {
                    return $this;
                }
                $this->sql['where'] .= $info['where'];
                $this->valueArr = array_merge($this->valueArr, $info['bind']);
                return $this;
            }
            $this->sql['where'] .= " {$paramArr['field']} {$symbol} :{$paramArr['instead']}";
            $this->valueArr[$paramArr['instead']] = $val;
        } else if (is_array($param)) {
            $fields = [];
            foreach ($param as $key => $value) {
                if (is_array($value)) {
                    if (count($value) !== 3) {
                        continue;
                    }
                    $k = $value[0];
                    $s = $value[1];
                    $v = $value[2];
                } else {
                    $k = $key;
                    $s = '=';
                    $v = $value;
                }
                $paramArr = $this->analysis($k);
                $symbol = strtoupper($s);
                if (in_array($symbol, ['IN', 'BETWEEN'], true) && !empty($v)) {
                    $info = $this->otherWhere($paramArr['field'], $symbol, $v);
                    if (empty($info)) {
                        continue;
                    }
                    $fields[] = $info['where'];
                    $this->valueArr = array_merge($this->valueArr, $info['bind']);
                } else {
                    $fields[] = "{$paramArr['field']} {$symbol} :{$paramArr['instead']}";
                    $this->valueArr[$paramArr['instead']] = $v;
                }
            }
            if (count($fields) > 0) {
                $this->sql['where'] .= " " . implode(' AND ', $fields);
            }
        }
        return $this;
    }


    /**
     * @param string $field
     * @param string $symbol
     * @param $val
     */
    protected function otherWhere(string $field, string $symbol, $val): ?array
    {
        $ch = strtolower($symbol);
        switch ($ch) {
            case 'not in':
                $ch = 'not_in';
                break;
        }
        $arr = is_array($val) ? $val : (is_numeric($val) || !empty($val) ? explode(',', $val) : []);
        $result = [
            'bind' => [],
            'where' => ''
        ];
        foreach ($arr as &$value) {
            $before = $value;
            $value = "{$ch}_{$this->parseAgr}";
            $result['bind'][$value] = $before;
            $value = ":{$value}";
            $this->parseAgr++;
        }
        unset($value);
        if (in_array($symbol, ['IN', 'NOT IN'], true)) {
            $result['where'] .= " {$field} {$symbol} (" . implode(',', $arr) . ")";
        }
        if ($symbol === 'BETWEEN') {
            if (!isset($arr[0]) || !isset($arr[1])) {
                return null;
            }
            $result['where'] .= " {$field} {$symbol} {$arr[0]} AND {$arr[1]}";
        }
        return $result;
    }

    //解析.
    private function analysis(string $arr = ''): array
    {
        $keyArr = [];
        $paramArr = explode('.', $arr);
        $instead = implode('_', $paramArr);
        $field = array_pop($paramArr);
        $beforeInfo = implode('.', $paramArr);
        $keyArr['field'] = empty($beforeInfo) ? "`{$field}`" : "{$beforeInfo}.`{$field}`";
        $keyArr['instead'] = "{$instead}_{$this->parseAgr}";
        $this->parseAgr++;
        return $keyArr;
    }

    //子查询
    public function whereChild(string $param = '', string $symbol = '', \Closure $val = null): self
    {
        $this->outputParseSql = true;
        $temp = [];
        $noTempField = ['from'];
        foreach ($this->sql as $key => $value) {
            if (!in_array($key, $noTempField)) {
                $temp[$key] = $value;
            }
        }
        $childSql = $val($this);
        foreach ($temp as $key => $value) {
            $this->sql[$key] = $value;
        }
        $this->updateExp = [];
        $this->sql['where'] = "WHERE `{$param}` {$symbol} ({$childSql})";
        return $this;
    }

    //whereOr条件
    public function whereOr(string $param = '', string $symbol = '', string $val = ''): self
    {
        $paramArr = $this->analysis($param);
        $symbol = strtoupper($symbol);
        $this->sql['where'] .= " OR {$paramArr['field']} {$symbol} :or_{$paramArr['instead']}";
        $this->valueArr['or_' . $paramArr['instead']] = $val;
        return $this;
    }

    //查询一条
    public function find()
    {
        $this->sql['limit'] = 'LIMIT 1';
        if (empty($this->sql['fields'])) {
            $this->sql['fields'] = '*';
        }
        $sqlInfoArr = array_filter($this->sql, function ($value) {
            return !empty($value);
        });
        $runSql = 'SELECT ' . implode(' ', $sqlInfoArr);
        if ($this->outputParseSql) {
            $this->outputParseSql = false;
            return $runSql;
        }
        $result = $this->query($runSql, $this->valueArr);
        $resultData = $result->fetch(\PDO::FETCH_ASSOC);
        if ($this->recordLog) {
            $this->logObj->info("pdoDb@find:querySql:" . $runSql . "\nvalues:" . var_export($this->valueArr, true) . "\nfind:res : " . var_export($resultData, true));
        }
        return is_callable($this->filterFunction) ? call_user_func($this->filterFunction, $resultData) : $resultData;
    }

    //select
    public function select()
    {
        if (empty($this->sql['fields'])) {
            $this->sql['fields'] = '*';
        }
        $sqlInfoArr = array_filter($this->sql, function ($value) {
            return !empty($value);
        });
        $runSql = 'SELECT ' . implode(' ', $sqlInfoArr);
        if ($this->outputParseSql) {
            $this->outputParseSql = false;
            return $runSql;
        }
        if (!empty($this->sql['union'])) {
            $this->valueArr = array_merge($this->valueArr, $this->unionWhere);
        }
        $result = $this->query($runSql, $this->valueArr);
        if ($result === false) {
            return false;
        }
        $resultData = $result->fetchAll(\PDO::FETCH_ASSOC);
        if ($this->recordLog) {
            $this->logObj->info("pdoDb@select:querySql:" . $runSql . "\nvalues:" . var_export($this->valueArr, true) . "\nselect:res : " . var_export($resultData, true));
        }
        return is_callable($this->filterFunction) ? array_map($this->filterFunction, $resultData) : $resultData;
    }

    //获取请求前解析的数据
    public function getBeforeQueryParse(): array
    {
        $this->parse++;
        $this->outputParseSql = true;
        $result = [];
        $result['sql'] = $this->select();
        $value = [];
        foreach ($this->valueArr as $key => $v) {
            $result['sql'] = str_replace(':' . $key, ':' . $key . '_un_' . $this->parse, $result['sql']);
            $value[$key . '_un_' . $this->parse] = $v;
        }
        $this->valueArr = [];
        $result['bindData'] = $value;
        return $result;
    }

    //添加过滤方法
    public function filter(\Closure $fun): self
    {
        $this->filterFunction = $fun;
        return $this;
    }


    public function getParseSql()
    {
        $this->outputParseSql = true;
    }

    //设置字段
    public function fields(string $str = ''): self
    {
        $fields = explode(',', $str);
        $pattern = '/\(|\)/';
        foreach ($fields as $key => $value) {
            //判断是否使用了函数
            if (strpos($value, '(') && strpos($value, ')')) {
                //正则过滤函数
                $arr = preg_split($pattern, trim($value));
                $alias = '';
                //判断是否取别名
                if (!empty($arr[2])) {
                    //取出别名信息组合
                    $aliasArr = preg_split('/\s+/', trim($arr[2]));
                    $alias = " " . $aliasArr[0] . " `{$aliasArr[1]}`";
                }
                $fieldsArr = $this->analysis($arr[1]);
                $fields[$key] = "{$arr[0]}({$fieldsArr['field']}){$alias}";
            } else if (preg_match('/\s+as\s+/', $value)) {
                $aliasArr = preg_split('/\s+/', trim($value));
                $fieldsArr = $this->analysis($aliasArr[0]);
                $fields[$key] = $fieldsArr['field'] . ' ' . $aliasArr[1] . ' ' . $aliasArr[2];
            } else {
                $fieldsArr = $this->analysis($value);
                $fields[$key] = $fieldsArr['field'];
            }
            $this->sql['fields'] = implode(',', $fields);
        }

        return $this;
    }

    //分组
    public function group(string $str = '', $flag = true): self
    {
        if ($flag !== false) {
            $group = explode(',', $str);
            $strArr = array();
            foreach ($group as $value) {
                $fieldsArr = $this->analysis($value);
                $strArr[] = $fieldsArr['field'];
            }
            $str = implode(',', $strArr);
        }
        $this->sql['group'] = 'GROUP BY ' . $str;
        return $this;

    }

    //连表操作
    public function join(string $method = '', string $table = '', $condition = ''): self
    {
        $method = strtoupper($method);
        $tableArr = preg_split('/\s+/', trim($table));
        $tableField = $this->analysis($tableArr[0]);
        if (is_array($condition)) {
            $joinArr = array();
            foreach ($condition as $key => $value) {
                $keyArr = $this->analysis($key);
                $valueArr = $this->analysis($value);
                $joinArr[] = "{$keyArr['field']}={$valueArr['field']}";
            }
            $joinStr = implode(' AND ', $joinArr);
        } else {
            $condition = explode('=', str_replace(' ', '', $condition));
            $keyArr = $this->analysis($condition[0]);
            $valueArr = $this->analysis($condition[1]);
            $joinStr = "{$keyArr['field']} = {$valueArr['field']}";
        }
        $this->sql['join'] .= "{$method} JOIN {$tableField['field']}";
        $this->sql['join'] .= count($tableArr) >= 3 ? " {$tableArr[1]} {$tableArr[2]}" : "";
        $this->sql['join'] .= " ON {$joinStr}";
        return $this;
    }


    //排序
    public function order(string $str): self
    {
        $orderBy = explode(',', $str);
        $items = [];
        foreach ($orderBy as $value) {
            //分割字符和排序关键字
            $order = explode(' ', $value);
            $orderArr = $this->analysis($order[0]);
            //组装排序SQL
            $items[] = "{$orderArr['field']} " . strtoupper($order[1]);
        }
        $this->sql['order'] = "ORDER BY " . implode(',', $items);
        return $this;

    }

    //having:只支持聚合函数过滤
    public function having($param = '', string $symbol = '', string $val = '', string $opt = 'and'): self
    {
        $pattern = '/\(|\)/';
        $fieldArr = [
            'field' => $param,
            'instead' => $param
        ];
        if (strpos($param, '(') && strpos($param, ')')) {
            $valueArr = preg_split($pattern, trim($param));
            $fieldArr = $this->analysis($valueArr[1]);
            $fieldArr['field'] = "{$valueArr[0]}({$fieldArr['field']})";
        }
        $this->sql['having'] .= empty($this->sql['having']) ? 'HAVING' : ' ' . strtoupper($opt);
        $this->sql['having'] .= " {$fieldArr['field']} {$symbol} :having_{$fieldArr['instead']}";
        $this->valueArr['having_' . $fieldArr['instead']] = $val;
        return $this;
    }

    //limit
    public function limit(int $start = 0, int $end = -1): self
    {
        $this->sql['limit'] = "LIMIT {$start}";
        $end != -1 && $this->sql['limit'] .= ",{$end}";
        return $this;
    }

    //分页查询
    public function paging(int $page = 1, int $row = 0): self
    {
        $page = $page == 0 ? 1 : $page;
        $start = ($page - 1) * $row;
        $this->sql['limit'] = "LIMIT {$start},{$row}";
        return $this;
    }

    //union
    public function union(array $parseSql, string $opt = 'union'): self
    {
        if (!count($parseSql)) {
            return $this;
        }
        $opt = strtoupper($opt);
        $strJoin = '';
        foreach ($parseSql as $value) {
            $strJoin .= ' ' . $opt . '  ' . $value['sql'] . ' ';
            $this->unionWhere = array_merge($this->unionWhere, $value['bindData']);
        }
        $this->sql['union'] = $strJoin;
        return $this;
    }

    //插入语句 支持一条['field'=>$value]与多条插入[[],[]]
    public function insert(array $arr)
    {
        if (!count($arr)) {
            return false;
        }
        $splice = '';
        if (is_array(current($arr))) {
            $index = 0;
            foreach ($arr as $value) {
                $splice .= $this->parseInsert($value, $index++);
            }
        } else {
            $splice .= $this->parseInsert($arr);
        }
        $sql = "INSERT INTO {$this->sql['table']} {$splice}";
        if ($this->outputParseSql) {
            $this->outputParseSql = false;
            return $sql;
        }
        $insertRes = $this->query($sql, $this->valueArr)->rowCount();
        if ($this->recordLog) {
            $this->logObj->info("pdoDb@insert:querySql:" . $sql . "\nvalues:" . var_export($this->valueArr, true) . "\ninsert:res : " . var_export($insertRes, true));
        }
        return $insertRes;
    }

    //解析insert数据
    private function parseInsert(array $arr = array(), int $index = 0): string
    {
        $fields = [];
        $placeholder = [];
        foreach ($arr as $key => $value) {
            $index === 0 && array_push($fields, "`{$key}`");
            $placeholder[] = ":{$key}_{$index}";
            $this->valueArr["{$key}_{$index}"] = $value;
        }
        $sql = $index === 0 ? '(' . implode(',', $fields) . ') VALUES ' : ',';
        $sql .= '(' . implode(',', $placeholder) . ')';
        return $sql;
    }


    /**
     * 插入数据并获得自增id
     * @param array $arr
     * @throws PdoCustomException
     */
    public function getInsertLastId(array $arr)
    {
        $auto = $this->autoRetFlag;
        $this->setAutoRet(false);
        $res = $this->insert($arr);
        $rs = -1;
        if ($res && $res > 0) {
          $rs =  $this->getPdoConnectInfo()->lastInsertId();
        }
        $this->setAutoRet($auto);
        $this->doneRun();
        return $rs;
    }

    //删除数据

    /**
     * @throws \Exception
     */
    public function delete()
    {
        if (empty($this->sql['where'])) {
            throw new \Exception('Missing WHERE condition on delete');
        }
        $sql = "DELETE {$this->sql['from']} {$this->sql['table']} {$this->sql['where']}";
        if ($this->outputParseSql) {
            $this->outputParseSql = false;
            return $sql;
        }
        $delRes = $this->query($sql, $this->valueArr)->rowCount();
        if ($this->recordLog) {
            $this->logObj->info("pdoDb@delete:querySql:" . $sql . "\nvalues:" . var_export($this->valueArr, true) . "\ndelete:res : " . var_export($delRes, true));
        }
        return $delRes;
    }

    //更新操作
    public function update(array $arr = array())
    {
        if (empty($this->sql['where'])) {
            throw new \PDOException('Missing WHERE condition on update');
        }
        $updateItems = array();
        foreach ($arr as $key => $value) {
            $updateItems[] = "`{$key}`= :update_{$key}";
            $this->valueArr["update_{$key}"] = $value;
        }
        foreach ($this->updateExp as $value) {
            $updateItems[] = $value;
        }
        $splice = implode(',', $updateItems);
        $sql = "UPDATE {$this->sql['table']} SET {$splice} {$this->sql['where']}";
        if ($this->outputParseSql) {
            $this->outputParseSql = false;
            return $sql;
        }
        $updateRes = $this->query($sql, $this->valueArr)->rowCount();
        if ($this->recordLog) {
            $this->logObj->info("pdoDb@update:querySql:" . $sql . "\nvalues:" . var_export($this->valueArr, true) . "\nupdate:res : " . var_export($updateRes, true));
        }
        return $updateRes;
    }


    //适用与更新操作，字符加一
    public function inc(string $field = '', int $number = 1): self
    {
        $this->updateExp[] = "`{$field}`=`{$field}` + :update_inc_{$field}";
        $this->valueArr["update_inc_{$field}"] = $number;
        return $this;
    }

    //适用与更新操作，字符减一
    public function dec(string $field = '', int $number = 1): self
    {
        $this->updateExp[] = "`{$field}`=`{$field}` - :update_dec_{$field}";
        $this->valueArr["update_dec_{$field}"] = $number;
        return $this;
    }

    //适用于更新操作，拼接字符串，替换字符串例如exp('name',concat('name,拼接的字符)')，等类似的操作
    public function exp(string $field = '', string $value = ''): self
    {
        $pattern = '/\(|\)/';
        if (is_string($value) && !empty($value)) {
            $valueSplitArr = preg_split($pattern, trim($value));
            $valueArr = explode(',', $valueSplitArr[1]);
            foreach ($valueArr as $key => &$val) {
                if (empty($val)) {
                    unset($val);
                    continue;
                }
                if ($key == 0 && trim($val) == $field) {
                    $val = "`{$field}`";
                    continue;
                }
                $str = $val;
                $val = "update_{$field}_{$this->parseAgr}";
                $this->valueArr[$val] = $str;
                $this->parseAgr++;
                $val = ".{$val}";
            }
            unset($val);
            $result = $valueSplitArr[0] . '(' . implode(',', $valueArr) . ')';
            $this->updateExp[] = "`{$field}`={$result}";
        }
        return $this;
    }


    //统计
    public function count(string $field = '')
    {
        $field = empty($field) ? '*' : $this->analysis($field)['field'];
        $this->sql['fields'] = "count({$field}) as count";
        $parseSql = $this->outputParseSql;
        $result = $this->find();
        return $parseSql ? $result : (int)$result['count'];
    }


    /**
     * 执行sql语句
     * @param string $sql
     * @param array $parameters
     * @return null|\PDOStatement
     * @throws PdoCustomException
     */
    public function query(string $sql = '', array $parameters = []): ?\PDOStatement
    {
        if ($this->queryFlag) {
            return null;
        }
        $this->queryFlag = true;
        if ($this->sqlEscape) {
            $parameters = HelperFun::filterSlashesArr($parameters);
        }
        if ($this->recordLog) {
            $this->logObj->info("pdoDb:{$this->optionsDbKey} : {$sql}");
            $this->logObj->info("pdoDb:bind : " . var_export($parameters, true));
        }
        //预准备语句
        $stmt = $this->getPdoConnectInfo()->prepare($sql);
        //执行SQL语句
        $stmt->execute($parameters);
        $this->queryFlag = false;
        $this->doneRun();
        return $stmt;
    }

    //加锁
    public function lock($str = false): self
    {
        if (!$str) {
            return $this;
        }
        $this->sql['lock'] = $str === true ? ' FOR UPDATE' : ' ' . $str;
        return $this;
    }

    /**
     * 调用结束
     */
    public function doneRun()
    {
        if ($this->autoRetFlag) {
            $this->getPdoConnectInfo()->recycleLink();
        }
    }


}