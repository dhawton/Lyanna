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

    protected static $_default_config = array(
        'connection_string' => 'sqlite::memory:',
        'id_column' => 'id',
        'id_column_overrides' => array(),
        'error_mode' => \PDO::ERRMODE_EXCEPTION,
        'username' => null,
        'password' => null,
        'driver_options' => null,
        'identifier_quote_character' => null,
        'limit_clause_style' => null,
        'logging' => false,
        'logger' => null,
        'caching' => false,
        'caching_auto_clear' => false,
        'return_result_sets' => false
    );
    protected static $_config = array();
    protected static $_db = array();
    protected static $_last_query;
    protected static $_query_log;
    protected static $_query_cache;
    protected static $_last_statement = null;

    protected $_connection_name;
    protected $_table_name;
    protected $_table_alias = null;
    protected $_values = array();
    protected $_result_columns = array();
    protected $_using_default_result_columns = true;
    protected $_join_sources = array();
    protected $_distinct = false;
    protected $_is_raw_query = false;
    protected $_raw_query = null;
    protected $_raw_parameters = null;
    protected $_where_conditions = null;
    protected $_limit = null;
    protected $_offset = null;
    protected $_order_by = array();
    protected $_group_by = array();
    protected $_having_conditions = array();
    protected $_data = array();
    protected $_dirty_fields = array();
    protected $_expr_fields = array();
    protected $_is_new = false;
    protected $_instance_id_column = null;

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

    protected static function _setupdDb($connection_name = self::DEFAULT_CONNECTION)
    {
        if (!array_key_exists($connection_name, self::$_db)
            || !is_object(self::$_db[$connection_name]))
            self::_setupDbConfig($connection_name);

        $db = new \PDO(self::$_config[$connection_name]['connection_string'],
                        self::$_config[$connection_name]['username'],
                        self::$_config[$connection_name]['password'],
                        self::$_config[$connection_name]['driver_options']);

        $db->setAttribute(\PDO::ATTR_ERRMODE, self::$_config[$connection_name]['error_mode']);
        self::set_db($db, $connection_name);
    }

    protected static function _setupDbConfig($connection_name)
    {
        if (!array_key_exists($connection_name, self::$_config))
            self::$_config[$connection_name] = self::$_default_config;
    }

    public static function set_db($db, $connection_name = self::DEFAULT_CONNECTION)
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
        return self::$_last_statement;
    }

    protected static function _execute($query, $parameters = array(), $connection = self::DEFAULT_CONNECTION)
    {
        $statement = self::getDb($connection)->prepare($query);
        self::$_last_statement = $statement;
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
        self::_logQuery($query, $parameters, $connection, (microtime(true)-$time));
    }

    protected static function _logQuery($query, $parameters, $connection, $query_time)
}