<?php
namespace Lyanna\Database;


class Database
    implements \ArrayAccess
{
    const CONDITION_FRAGMENT = 0;
    const CONDITION_VALUES = 1;
    const DEFAULT_CONNECTION = 'default';
    const LIMIT_STYLE_TOP_N = "top";
    const LIMIT_STYLE_LIMIT = "limit";

    protected static $_defaultConfig = array(
        'connection_string' => 'sqlite::memory:',
        'id_column' => 'id',
        'id_column_overrides' => array(),
        'error_mode' => \PDO::ERRMODE_EXCEPTION,
        'username' => null,
        'password' => null,
        'driver_options' => null,
        'identifier_quote_character' => null,
        'limit_clause_style' => null,
        'caching' => false,
        'caching_auto_clear' => false,
        'return_result_sets' => false
    );
    protected static $_config = array();
    protected static $_db = array();
    protected static $_lastQuery;
    protected static $_queryCache;
    protected static $_lastStatement = null;

    protected $_connection;
    protected $_tableName;
    protected $_tableAlias = null;
    protected $_values = array();
    protected $_resultColumns = array();
    protected $_usingDefaultResultColumns = true;
    protected $_joinSources = array();
    protected $_distinct = false;
    protected $_isRawQuery = false;
    protected $_rawQuery = null;
    protected $_rawParameters = null;
    protected $_whereConditions = null;
    protected $_limit = null;
    protected $_offset = null;
    protected $_orderBy = array();
    protected $_groupBy = array();
    protected $_havingConditions = array();
    protected $_data = array();
    protected $_dirtyFields = array();
    protected $_exprFields = array();
    protected $_isNew = false;
    protected $_instanceIdColumn = null;

    public static function config($key, $value = null, $connection_name = self::DEFAULT_CONNECTION)
    {
        self::_setupDbConfig($connection_name);

        if (is_array($key))
            foreach ($key as $confkey => $confval)
                self::config($confkey, $confval, $connection_name);
        else {
            if (is_null($value)) {
                $value = $key;
                $key = "connection_string";
            }
            self::$_config[$connection_name][$key] = $value;
        }
    }

    public static function getConfig($key = null, $connection_name = self::DEFAULT_CONNECTION)
    {
        if ($key)
            return self::$_config[$connection_name][$key];
        else
            return self::$_config[$connection_name];
    }

    public static function resetConfig()
    {
        self::$_config = array();
    }

    protected static function _setupdDb($connection = self::DEFAULT_CONNECTION)
    {
        if (!array_key_exists($connection, self::$_db)
            || !is_object(self::$_db[$connection]))
            self::_setupDbConfig($connection);

        $db = new \PDO(self::$_config[$connection]['connection_string'],
                        self::$_config[$connection]['username'],
                        self::$_config[$connection]['password'],
                        self::$_config[$connection]['driver_options']);

        $db->setAttribute(\PDO::ATTR_ERRMODE, self::$_config[$connection]['error_mode']);
        self::setDb($db, $connection);
    }

    protected static function _setupDbConfig($connection_name)
    {
        if (!array_key_exists($connection_name, self::$_config))
            self::$_config[$connection_name] = self::$_defaultConfig;
    }

    public static function setDb($db, $connection_name = self::DEFAULT_CONNECTION)
    {
        self::_setupDbConfig($connection_name);
        self::$_db[$connection_name] = $db;
        if (!is_null(self::$_db[$connection_name])) {
            self::_setupIdentifierQuoteCharacter($connection_name);
            self::_setupLimitClauseStyle($connection_name);
        }
    }

    public static function forTable($table, $connection = self::DEFAULT_CONNECTION)
    {
        self::_setupdDb($connection);
        return new self($table, array(), $connection);
    }

    public static function resetDb()
    {
        self::$_db = array();
    }

    protected static function _setupIdentifierQuoteCharacter($connection)
    {
        if (is_null(self::$_config[$connection]['identifier_quote_character']))
            self::$_config[$connection]['identifier_quote_character'] =
                self::_detectIdentifierQuoteCharacter($connection);
    }

    public static function _setupLimitClauseStyle($connection)
    {
        if (is_null(self::$_config[$connection]['limit_clause_style']))
            self::$_config[$connection]['limit_clause_style'] =
                self::_detectLimitClauseStyle($connection);
    }

    protected static function _detectIdentifierQuoteCharacter($connection)
    {
        switch (self::getDb($connection)->getAttribute(\PDO::ATTR_DRIVER_NAME)) {
            case 'pgsql':
            case 'sqlsrv':
            case 'dblib':
            case 'mssql':
            case 'sybase':
            case 'firebird':
                return '"';
            case 'mysql':
            case 'sqlite':
            case 'sqlite2':
                case '`';
        }
    }

    protected static function _detectLimitClauseStyle($connection)
    {
        switch (self::getDb($connection)->getAttribute(\PDO::ATTR_DRIVER_NAME)) {
            case 'sqlsrv':
            case 'dblib':
            case 'mssql':
                return Database::LIMIT_STYLE_TOP_N;
            default:
                return Database::LIMIT_STYLE_LIMIT;
        }
    }

    public static function getDb($connection = self::DEFAULT_CONNECTION)
    {
        self::_setupdDb($connection);
        return self::$_db[$connection];
    }

    public static function rawExecute($query, $parameters = array(), $connection = self::DEFAULT_CONNECTION)
    {
        self::_setupdDb($connection);
        self::_execute($query, $parameters, $connection);
    }

    public static function getLastStatement()
    {
        return self::$_lastStatement;
    }

    protected static function _execute($query, $parameters = array(), $connection = self::DEFAULT_CONNECTION)
    {
        $statement = self::getDb($connection)->prepare($query);
        self::$_lastStatement = $statement;
        $time = microtime(true);
        foreach ($parameters as $key => $param) {
            if (is_null($param))
                $type = \PDO::PARAM_NULL;
            elseif (is_bool($param))
                $type = \PDO::PARAM_BOOL;
            elseif (is_int($param))
                $type = \PDO::PARAM_INT;
            else
                $type = \PDO::PARAM_STR;

            $statement->bindParam(is_int($key)? ++$key : $key, $param, $type);
        }

        $q = $statement->execute();
    }

    public static function getConnections()
    {
        return array_keys(self::$_db);
    }

    protected function __construct($table, $data = array(), $connection = self::DEFAULT_CONNECTION)
    {
        $this->_tableName = $table;
        $this->_data = $data;
        $this->_connection = $connection;
        self::_setupDbConfig($connection);
    }

    // Create a new empty instance
    public function create($data=null)
    {
        $this->_isNew = true;
        if (!is_null($data))
            return $this->populate($data)->forceAllDirty();

        return $this;
    }

    public function useIdColumn($column)
    {
        $this->_instanceIdColumn = $column;
        return $this;
    }

    protected function _createInstanceFromRow($row)
    {
        $instance = self::forTable($this->_tableName, $this->_connection);
        $instance->useIdColumn($this->_instanceIdColumn);
        $instance->populate($row);
        return $instance;
    }

    public function findOne($id=null)
    {
        if (!is_null($id))
            $this->whereIdIs($id);

        $this->limit(1);
        $rows = $this->_run();

        if (empty($rows))
            return false;

        return $this->_createInstanceFromRow($rows[0]);
    }

    public function findMany()
    {
        if (self::$_config[$this->_connection]['return_result_sets'])
            return $this->findResultSet();

        return $this->_findMany();
    }

    protected function _findMany()
    {
        $rows = $this->_run();
        return array_map(array($this, '_createInstanceFromRow'), $rows);
    }

    public function findResultSet()
    {
        return new DHResultSet($this->_findMany());
    }

    public function findArray()
    {
        return $this->_run();
    }

    public function count($column = '*')
    {
        return $this->_callAggregateDbFunction(__FUNCTION__, $column);
    }

    public function max($column)
    {
        return $this->_callAggregateDbFunction(__FUNCTION__, $column);
    }

    public function min($column)
    {
        return $this->_callAggregateDbFunction(__FUNCTION__, $column);
    }

    public function avg($column)
    {
        return $this->_callAggregateDbFunction(__FUNCTION__, $column);
    }

    public function sum($column)
    {
        return $this->_callAggregateDbFunction(__FUNCTION__, $column);
    }

    public function _callAggregateDbFunction($func, $col)
    {
        $alias = strtolower($func);
        $function = strtoupper($func);
        if ($col != '*')
            $col = $this->_quoteIdentifier($col);
        $resultColumns = $this->_resultColumns;
        $this->_resultColumns = array();
        $this->selectExpression("$function($col)", $alias);
        $result = $this->findOne();
        $this->_resultColumns = $resultColumns;

        $return = 0;
        if ($result !== false && isset($result->alias)) {
            if (!is_numeric($result->$alias))
                $return = $result->$alias;
            elseif ((int)$result->$alias == (float)$result->$alias)
                $return = (int)$result->$alias;
            else
                $return = (float)$result->$alias;
        }

        return $return;
    }

    // Populate instance of class from associative array
    public function populate($data=array())
    {
        $this->_data = $data;
        return $this;
    }

    // Force all fields to dirty so they will be updated when ->save() called.
    public function forceAllDirty()
    {
        $this->_dirtyFields = $this->_data;
        return $this;
    }

    public function rawQuery($query, $parameters = array())
    {
        $this->_isRawQuery = true;
        $this->_rawQuery = $query;
        $this->_rawParameters = $parameters;
        return $this;
    }

    public function tableAlias($alias)
    {
        $this->_tableAlias = $alias;
        return $this;
    }

    public function addResultColumn($expression, $alias = null)
    {
        if (!is_null($alias))
            $expression .= " AS " . $this->_quoteIdentifier($alias);

        if ($this->_usingDefaultResultColumns) {
            $this->_resultColumns = array($expression);
            $this->_usingDefaultResultColumns = false;
        } else
            $this->_resultColumns[] = $expression;

        return $this;
    }

    public function countNullIdColumns()
    {
        if (is_array($this->_getIdColumnName()))
            return count(array_filter($this->id(), 'is_null'));
        else
            return is_null($this->id()) ? 1 : 0;
    }

    public function select($column, $alias = null)
    {
        $column = $this->_quoteIdentifier($column);
        return $this->_addResultColumn($column, $alias);
    }

    public function selectExpression($expression, $alias = null)
    {
        return $this->_addResultColumn($expression, $alias);
    }

    // Defaults to '*',
    // Supports selectMany('col1','2','3','4'..)
    //          selectMany(array('1','2','3',...))
    //      and selectMany('1', '2', '3', array('4', '5', '6'))
    // For aliases, use array('alias' => 'column', 'alias2' => 'column2')
    // Applies to selectMany and selectManyExpression
    public function selectMany()
    {
        $columns = func_get_args();
        if (!empty($columns)) {
            $columns = $this->_normalizeSelectManyColumns($columns);

            foreach ($columns as $alias => $column) {
                if (is_numeric($alias))
                    $alias = null;

                $this->select($column, $alias);
            }
        }

        return $this;
    }

    public function selectManyExpression()
    {
        $columns = func_get_args();

        if (!empty($columns)) {
            $columns = $this->_normalizeSelectManyColumns($columns);

            foreach ($columns as $alias => $column) {
                if (is_numeric($alias))
                    $alias = null;

                $this->selectExpression($column, $alias);
            }
        }

        return $this;
    }

    protected function _normalizeSelectManyColumns($columns)
    {
        $return = array();

        foreach ($columns as $column) {
            if (is_array($column))
                foreach ($column as $key => $value) {
                    if (!is_numeric($key))
                        $return[$key] = $value;
                    else
                        $return[] = $value;
                }
            else
                $result[] = $column;
        }

        return $return;
    }

    public function distinct()
    {
        $this->_distinct = true;
        return $this;
    }
}