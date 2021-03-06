<?php
/**
 * Database tool
 *
 * @author frantisek.kaspar
 *
 */
class FDBTool
{

    /**
     * 0 - number indexed data
     * 1 - name indexed
     * @var int
     */
    public $fetchmode = 0;
    /**
     *cacheResults - driver
     *
     * 0 - no cache
     * l - cache per load
     * s - cache per session
     * d - database
     * f - cache in file
     **/
    public $cacheResults = 0;

    /**
     *
     * cache group
     *
     */
    public $cacheGroup = 'fdb';

    /**
     * lifetime in seconds of cached results
     *
     * @var Number
     */
    public $lifeTime = 0;

    /**
     * print debug on screen
     * */
    public $debug = 0;
    /**
     * profiler - logs in file queries and times
     *
     * @var Boolean
     */
    const profilerEnabled              = true;
    private static $profilerHandle     = -1;
    private static $profilerHandleSlow = -1;

    public $queryTemplate = 'select {SELECT} from {TABLE} {JOIN} where {WHERE} {GROUP} {ORDER} {LIMIT}';
    public $table         = '';
    public $primaryCol    = '';
    public $columns;
    public $VO;

    private $_where      = array();
    private $_order      = array();
    private $_select     = array('*');
    private $selectCount = 'count(1)';
    private $_group      = array();
    private $autojoin    = false;
    public function autojoinSet($v)
    {
        $this->autojoin = $v;
    }
    private $_join  = '';
    private $_limit = array();

    private $openingDelimiter   = '{#';
    private $closingDelimiter   = '#}';
    private $variablenameRegExp = '[\.0-9A-Za-z_-]+';
    private $variablesRegExp;
    private $replaceKeys;
    private $replaceVars = array(
        'date'           => '%Y-%m-%d',
        'date_iso'       => '%Y-%m-%d',
        'date_local'     => '%d.%m.%Y',
        'time'           => '%T',
        'time_short'     => '%H:%i',
        'datetime_iso'   => '%Y-%m-%d %T',
        'datetime_iso'   => '%Y-%m-%dT%T',
        'datetime_local' => '%T %d.%m.%Y',
    );

    //---save tool
    public $forceInsert = false;
    public $_cols       = array();
    public $_ignore     = array();
    public $_notQuoted  = array();
    public $quoteType   = "'";

    public function __construct($tableName = '', $primaryCol = '')
    {
        $this->debug           = FConf::get('db', 'debug');
        $this->table           = $tableName;
        $this->variablesRegExp = '@' . $this->openingDelimiter . '(' . $this->variablenameRegExp . ')' . $this->closingDelimiter . '@sm';
        $this->replaceKeys     = array_keys($this->replaceVars);
        $this->primaryCol      = $primaryCol;
    }

    public function queryReset()
    {
        $this->_where = array();
        $this->_join  = '';
        $this->_order = array();
        $this->_group = array();
    }
    public function setTemplate($template)
    {
        $this->queryTemplate = $template;
    }
    public function getTemplate()
    {
        return $this->queryTemplate;
    }
    public function getWhere()
    {
        if (empty($this->_where)) {
            $where[] = '1';
        } else if ($this->autojoin === true) {
            foreach ($this->_where as $cond) {
                if (!strpos($cond, '.') && strpos($cond, '(') === false) {
                    $cond = $this->table . '.' . trim($cond) . ' ';
                }
                $where[] = $cond;
            }
        } else {
            $where = $this->_where;
        }
        return ' ' . implode(' ', $where);
    }
    public function setWhere($whereCondition)
    {
        $this->_where = array($whereCondition);
    }
    public function addWhere($where, $condition = 'AND')
    {
        $len = count($this->_where);
        if ($len > 0) {
            $this->_where[$len - 1] .= ' ' . $condition;
        }
        $this->_where[] = ' ' . $where . ' ';
    }

    /**
     *
     *    @param order 0 - not ordering, 1-ascemding, 2-descending, 3-asc numeric, 4-desc numeric
     */
    public function joinOnPropertie($prop, $order = 0, $joinType = 'left join')
    {
        $this->autojoin = true;
        $this->addSelect($prop . '.value as ' . $prop . '_prop');
        $this->addJoin($joinType . ' ' . $this->table . '_properties as ' . $prop . ' on ' . $prop . '.' . $this->primaryCol . '=' . $this->table . '.' . $this->primaryCol . ' and ' . $prop . '.name = "' . $prop . '"');
        switch ($order) {
            case 1:
                $this->addOrder($prop . ".value");
                break;
            case 2:
                $this->addOrder($prop . ".value desc");
                break;
            case 3:
                $this->addOrder("(" . $prop . ".value+0.0)");
                break;
            case 4:
                $this->addOrder("(" . $prop . ".value+0.0) desc");
                break;
        }
    }

    public function addJoin($condition)
    {
        $this->_join .= ' ' . $condition;
    }

    public function addJoinAuto($table, $joinColumn, $selectColumnsArray, $type = 'LEFT JOIN')
    {
        $this->_join .= ' ' . $type . ' ' . $table . ' on ' . $this->table . '.' . $joinColumn . '=' . $table . '.' . $joinColumn;
        $this->autojoin = true;
        $len            = count($this->_select);
        if (!empty($selectColumnsArray)) {
            foreach ($selectColumnsArray as $col) {
                $this->addSelect(strpos($col, ' as ') === false ? $table . '.' . $col : $col);
            }
        }
    }
    public function getJoin()
    {
        return $this->_join;
    }
    public function addWhereSearch($column, $string, $condition = "AND", $groupCondition = "AND")
    {
        $string = str_replace('"', '""', str_replace(' ', '%', strtolower(trim($string))));
        if (is_array($column)) {
            foreach ($column as $col) {
                $arr[] = ' LOWER(' . $col . ') LIKE "%' . $string . '%" ';
                $str   = implode($condition, $arr);
            }
            $this->addWhere('(' . $str . ')', $groupCondition);
        } else {
            $this->addWhere('LOWER(' . $column . ') LIKE "%' . $string . '%"', $condition);
        }

    }
    public function addFulltextSearch($columns, $string, $condition = "AND", $queryExpansion = false)
    {
        //IN NATURAL LANGUAGE MODE - not working on station
        $this->addWhere('MATCH (' . $columns . ') AGAINST ("' . $string . '"' . (($queryExpansion == true) ? (' WITH QUERY EXPANSION') : ('')) . ')');
    }
    public function setOrder($orderCondition = '', $desc = false)
    {
        $orderCondition = $orderCondition . ($desc ? ' DESC' : '');
        $this->_order   = explode(',', $orderCondition);
    }
    public function addOrder($orderCondition = '', $desc = false)
    {
        $this->_order[] = $orderCondition . ($desc ? ' DESC' : '');
    }
    public function getOrder()
    {
        if ($this->autojoin === true) {
            foreach ($this->_order as $cond) {
                if (!strpos($cond, '.')) {
                    $cond = $this->table . '.' . trim($cond) . ' ';
                }
                $order[] = $cond;
            }
        } else {
            $order = $this->_order;
        }
        if (!empty($order)) {
            return ' order by ' . implode(',', $order);
        }

    }
    public function setSelect($what = '*')
    {
        if (is_array($what)) {
            $this->_select = $what;
        } else {
            $this->_select = explode(',', $what);
        }
    }
    public function addSelect($what = '*')
    {
        $this->_select[] = $what;
    }
    public function replaceSelect($what, $with)
    {
        $arrReplacement = explode(',', $with);
        if (count($arrReplacement) > 0) {
            foreach ($this->_select as $v) {
                if ($v != $what) {
                    $select[] = $v;
                } else {
                    foreach ($arrReplacement as $r) {
                        $select[] = $r;
                    }
                }
            }
            $this->_select = $select;
        }
    }
    public function getSelect()
    {
        if ($this->autojoin) {
            foreach ($this->_select as $col) {
                if (strpos($col, '.') === false && strpos($col, '(') === false) {
                    $col = $this->table . '.' . trim($col) . ' ';
                }

                $arrCols[] = $col;
            }
        } else {
            $arrCols = $this->_select;
        }
        return implode(',', $arrCols);
    }
    public function setLimit($from = 0, $count = 0)
    {
        if ($from == 0 && $count == 0) {
            $this->_limit = array();
        } else {
            $this->_limit = array($from, $count);
        }

    }
    public function getLimit()
    {
        return ((!empty($this->_limit)) ? (' limit ' . $this->_limit[0] . ',' . $this->_limit[1]) : (''));
    }
    public function setGroup($group = '')
    {
        $this->_group = explode(',', $group);
    }
    public function getGroup()
    {

        if ($this->autojoin === true) {
            foreach ($this->_group as $cond) {
                if (!strpos($cond, '.')) {
                    $cond = $this->table . '.' . $cond;
                }
                $group[] = $cond;
            }
        } else {
            $group = $this->_group;
        }
        if (!empty($group)) {
            return ' group by ' . implode(',', $group);
        }

    }
    public function buildBase()
    {
        $query = $this->queryTemplate;
        $query = str_replace('{TABLE}', $this->table, $query);
        $query = str_replace('{JOIN}', $this->getJoin(), $query);
        $query = str_replace('{WHERE}', $this->getWhere(), $query);
        $query = str_replace('{GROUP}', $this->getGroup(), $query);
        return $query;
    }
    public function replaceKeys($query)
    {
        preg_match_all($this->variablesRegExp, $query, $regs);
        $length = count($regs[1]);
        if ($length != 0) {
            $x = 0;
            while ($x < $length) {
                if (in_array($regs[1][$x], $this->replaceKeys)) {
                    $query = str_replace($regs[0][$x], $this->replaceVars[$regs[1][$x]], $query);
                } else {
                    die('FDBTool::key not exists:' . $regs[1][$x]);
                }

                $x++;
            }
        }
        return $query;
    }
    public function getUID($from = 0, $perPage = 0)
    {
        return md5($this->buildQuery($from, $perPage));
    }
    public function buildQuery($from = 0, $perPage = 0)
    {
        if ($perPage > 0) {
            $this->setLimit($from, $perPage);
        }

        $query = $this->buildBase();
        $query = str_replace('{SELECT}', $this->getSelect(), $query);
        $query = str_replace('{ORDER}', $this->getOrder(), $query);
        $query = str_replace('{LIMIT}', $this->getLimit(), $query);
        $query = $this->replaceKeys($query);
        return $query;
    }
    public function buildGetCount($count = '')
    {
        if ($count != '') {
            $this->selectCount = $count;
        }

        $query = $this->buildBase();
        $query = str_replace('{SELECT}', $this->selectCount, $query);
        $query = str_replace('{ORDER}', '', $query);
        $query = str_replace('{LIMIT}', '', $query);
        $query = $this->replaceKeys($query);
        return $query;
    }
    //---query run functions
    public function getCount()
    {
        $dot = $this->buildGetCount();
        if ($this->debug == 1) {
            echo "GETCOUNT RUN: " . $dot . " <br />\n";
        }

        return FDBTool::getOne($dot);
    }
    public function getContent($from = 0, $perPage = 0, $cacheId = false)
    {
        //validate from
        if ($from > 10 && $from * $perPage > 10000) {
            $cnt = $this->getCount();
            if ($cnt < $from * $perPage) {
                $from = $cnt;
            }
        }
        $dot = $this->buildQuery($from, $perPage);
        if ($this->debug == 1) {
            echo "GETCONTENT RUN: " . $dot . " <br />\n";
        }

        $cacheId = (($cacheId !== false) ? ($cacheId) : (md5($dot)));
        $arr     = FDBTool::getAll($dot, $cacheId, $this->cacheGroup, $this->cacheResults, $this->lifeTime, $this->fetchmode);
        if (!empty($arr)) {
            if (!empty($this->VO)) {
                if (class_exists($this->VO)) {
                    $i = 0;
                    foreach ($arr as $item) {
                        $vo = new $this->VO;
                        foreach ($item as $k => $v) {
                            if (property_exists($this->VO, $k)) {
                                $vo->{$k} = $v;
                            } else {
                                if (strpos($k, '_prop') !== false) {
                                    $propName = str_replace('_prop', '', $k);
                                    $propList = $vo->getPropertiesList();
                                    if (in_array($propName, $propList)) {
                                        $vo->properties->$propName = $v;
                                    }
                                }
                            }
                        }
                        $vo->loaded = true;
                        $vo->memStore();
                        $arr[$i] = $vo;
                        $i++;
                    }
                }
            }
            return $arr;
        }
    }
    public function getCacheId($id)
    {
        return $this->table . '-' . $this->primaryCol . '-' . $id . '-' . count($this->columns);
    }
    public function get($id = 0)
    {
        if (!empty($this->columns)) {
            foreach ($this->columns as $k => $v) {
                if (strpos($v, ' as ') === false) {
                    $v .= ' as ' . $k;
                }

                $columnsAsed[] = $v;
            }
            $this->replaceSelect('*', implode(',', $columnsAsed));
        }
        if (empty($this->_where)) {
            if (empty($id)) {
                return;
            }

            $primKeys = explode(',', $id);
            $primArr  = explode(',', $this->primaryCol);
            $i        = 0;
            foreach ($primArr as $col) {
                $this->addWhere((($this->autojoin == true) ? ($this->table . '.') : ('')) . $col . '="' . $primKeys[$i] . '"');
                $i++;
            }
            $ret = $this->getContent(0, 0, $this->getCacheId($id));
            if (!empty($ret)) {
                return $ret[0];
            }
        } else {
            $ret = $this->getContent();
            if (!empty($ret)) {
                return $ret[0];
            }
        }
        return false;
    }

    //---save functions
    public function resetIgnore()
    {
        $this->_ignore = array();
    }
    public function addIgnore($colName)
    {
        if (array_search($colName, $this->_ignore) === false) {
            $this->_ignore[] = $colName;
        }
    }
    public function addCol($name, $value, $quote = true)
    {
        $this->_cols[$name] = $value;
        if (!$quote) {
            $this->_notQuoted[] = $name;
        }

    }
    public function notQuote($colName)
    {
        if (array_search($colName, $this->_notQuoted) === false) {
            $this->_notQuoted[] = $colName;
        }
    }
    public function setCols($cols, $notQuoted = array())
    {
        $this->_cols = array();
        foreach ($cols as $k => $v) {
            $this->addCol($k, $v, ((in_array($k, $notQuoted)) ? (false) : (true)));
        }
    }

    public function buildInsert($cols = array(), $notQuoted = array())
    {
        if (!empty($cols)) {
            $this->setCols($cols, $notQuoted);
        }

        $cols = $this->quoteCols();
        $ret  = 'insert into ' . $this->table
        . ' (' . implode(",", array_keys($cols))
        . ') values ('
        . implode(',', $cols)
            . ')';
        return $ret;
    }

    public function buildUpdate($cols = array(), $notQuoted = array())
    {
        if (!empty($cols)) {
            $this->setCols($cols, $notQuoted);
        }

        $ret     = 'update ' . $this->table . ' set ';
        $cols    = $this->quoteCols();
        $first   = true;
        $primCol = explode(',', $this->primaryCol);
        foreach ($this->_cols as $k => $v) {
            if (!in_array($k, $this->_ignore) && !in_array($k, $primCol)) {
                $ret .= (($first) ? ('') : (',')) . $k . '=' . ((!in_array($k, $this->_notQuoted)) ? ($this->quote($v)) : ($v));
                $first = false;
            }
        }
        foreach ($primCol as $col) {
            $whereArr[] = $col . '=' . $this->quoteType . $this->_cols[$col] . $this->quoteType;
        }
        $ret .= ' where ' . implode(' and ', $whereArr);
        return $ret;
    }

    public static function getLastId()
    {
        return FDBTool::getOne("SELECT LAST_INSERT_ID()");
    }

    public function hasKey()
    {
        $ret     = true;
        $primArr = explode(',', $this->primaryCol);
        foreach ($primArr as $col) {
            if (empty($this->_cols[$col])) {
                $ret = false;
            }
        }
        if ($ret == true && count($primArr) > 1) {
            $multiKey = $this->multiKeyExists();
            if ($multiKey == 0) {
                $ret = false;
            }

        }
        return $ret;
    }

    public function getKey()
    {
        $primArr = explode(',', $this->primaryCol);
        foreach ($primArr as $col) {
            $arr[] = $this->_cols[$col];
        }
        if (count($arr) == 1) {
            return $arr[0];
        } else {
            return $arr;
        }
    }

    public function multiKeyExists()
    {
        $primArr = explode(',', $this->primaryCol);
        if (count($primArr) > 1) {
            $dbt = new FDBTool($this->table);
            foreach ($primArr as $col) {
                $dbt->addWhere($col . "='" . $this->_cols[$col] . "'");
            }
            return $dbt->getCount();
        }
    }

    public function save($cols = array(), $notQuoted = array())
    {
        if (!empty($cols)) {
            $this->setCols($cols, $notQuoted);
        }

        $insert = false;
        if (!$this->hasKey() || $this->forceInsert) {
            $dot    = $this->buildInsert();
            $insert = true;
        } else {
            $dot = $this->buildUpdate();
        }
        //---save
        if ($this->debug == 1) {
            echo $dot;
        }

        if (FDBTool::query($dot)) {
            if ($key = $this->hasKey()) {
                $retId = $this->getKey();
                if (is_array($retId)) {
                    $retId = implode(',', $retId);
                }

            } else if ($insert === true) {
                $retId = FDBTool::getLastId();
            }
        }
        //---invalidate cache
        if (!empty($this->cacheResults)) {
            $cache = FCache::getInstance($this->cacheResults);
            $cache->invalidateData($this->getCacheId($retId), 'fdb');
        }

        return $retId;
    }

    public function delete($id)
    {
        if (!is_array($id)) {
            $id = array($this->primaryCol => $id);
        }
        foreach ($id as $k => $v) {
            $delWhereArr[] = $k . "=" . $this->quoteType . $v . $this->quoteType;
        }
        return FDBTool::query("delete from " . $this->table . " where " . implode(' and ', $delWhereArr));
    }
    //---save support functions
    public function quote($str)
    {
        $str = str_replace($this->quoteType, '\\' . $this->quoteType, $str);
        $str = str_replace('\\\\', '\\', $str);
        return $this->quoteType . trim($str) . $this->quoteType;
    }
    public function quoteCols()
    {
        foreach ($this->_cols as $k => $v) {
            if (!in_array($k, $this->_ignore)) {
                $arr[$k] = ((!in_array($k, $this->_notQuoted)) ? ($this->quote($v)) : ($v));
            }
        }
        return $arr;
    }

    //---simple query
    public static function getAll($query, $key = null, $grp = 'default', $driver = 'l', $lifeTime = -1, $fetchmode = 0)
    {
        if (!empty($key) && $driver != '0') {
            //---cache results
            $cache = FCache::getInstance($driver, $lifeTime);
            $ret   = $cache->getData($key, $grp);
            if (false === $ret) {
                $ret = FDBTool::getData('getAll', $query, $fetchmode);
                $cache->setData($ret);
            }
        } else {
            //---no cache
            $ret = FDBTool::getData('getAll', $query, $fetchmode);
        }
        return $ret;
    }

    public static function getRow($query, $key = null, $grp = 'default', $driver = 'l', $lifeTime = -1, $fetchmode = 0)
    {
        if (!empty($key) && $driver != '0') {
            //---cache results
            $cache = FCache::getInstance($driver, $lifeTime);
            if (($ret = $cache->getData($key, $grp)) === false) {
                $ret = FDBTool::getData('getRow', $query, $fetchmode);
                $cache->setData($ret);
            }
        } else {
            //---no cache
            $ret = FDBTool::getData('getRow', $query, $fetchmode);
        }
        return $ret;
    }

    public static function getCol($query, $key = null, $grp = 'default', $driver = 'l', $lifeTime = -1)
    {
        if (!empty($key) && $driver != '0') {
            //---cache results
            $cache = FCache::getInstance($driver, $lifeTime);
            if (($ret = $cache->getData($key, $grp)) === false) {
                $ret = FDBTool::getData('getCol', $query);
                $cache->setData($ret);
            }
        } else {
            //---no cache
            $ret = FDBTool::getData('getCol', $query);
        }
        return $ret;
    }

    public static function getOne($query, $key = null, $grp = 'default', $driver = 'l', $lifeTime = -1)
    {
        if (!empty($key) && $driver != '0') {
            //---cache results
            $cache = FCache::getInstance($driver, $lifeTime);
            if (($ret = $cache->getData($key, $grp)) === false) {
                $ret = FDBTool::getData('getOne', $query);
                $cache->setData($ret);
            }
        } else {
            //---no cache
            $ret = FDBTool::getData('getOne', $query);
        }
        return $ret;
    }

    public static function query($query)
    {
        return FDBTool::getData('query', $query);
    }

    public static function queryLater($query)
    {
        $q = FQueue::getInstance();
        $q->push('query', $query);
    }

    private static function getData($function, $query, $fetchmode = 0)
    {
        $db = FDBConn::getInstance();
        //---stats
        if (FDBTool::profilerEnabled === true) {
            $start = FError::getmicrotime();
        }

        if ($fetchmode === 1) {
            $db->assoc = true;
        } else {
            $db->assoc = false;
        }

        $ret = $db->$function($query);

        //---stats
        if (FDBTool::profilerEnabled === true) {
            $text = preg_replace('/\s\s+/', ' ', $query);
            if (self::$profilerHandle == -1) {
                self::$profilerHandle = FProfiler::init(FConf::get('settings', 'logs_path') . FConf::host() . '-FDB.log');
            }

            FProfiler::write($text, self::$profilerHandle, $start);
            if ((FError::getmicrotime() - $start) > 1.5) {
                //SLOW LOG
                if (self::$profilerHandleSlow == -1) {
                    self::$profilerHandleSlow = FProfiler::init(FConf::get('settings', 'logs_path') . FConf::host() . '-FDB-slow.log', false);
                }

                FProfiler::write($text, self::$profilerHandleSlow, $start);
            }
        }
        return $ret;
    }

}
