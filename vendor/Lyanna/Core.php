<?php


namespace Lyanna;


class Core
{
    protected $instanceClasses = array();
    protected $instances = array();
    protected $modules = array(
        'db' => '\Lyanna\Database',
        'orm' => '\Lyanna\ORM'
    );
    private $core;
    public static $ref;
    private $globals = array();

    public function setGlobal($key, $value)
    {
        $this->globals[$key] = $value;
    }

    public function getGlobal($key)
    {
        if ($this->globals[$key] == null && $key == "user") throw new \Exception("User requested but is null");
        return $this->globals[$key];
    }

    public function __get($name) {
        if (isset($this->instances[$name]))
            return $this->instances[$name];

        if (isset($this->instanceClasses[$name]))
            return $this->instances[$name] = new $this->instanceClasses[$name]($this);

        if (isset($this->modules[$name]))
            return $this->instances[$name] = new $this->modules[$name]($this);

        throw new \Exception("Property {$name} not found on ".get_class($this));
    }

    public function __construct()
    {
        if (Config::get("db.enabled") == true) {
            $this->__databaseServer = Config::get('db.server');
            $this->__databaseUser = Config::get("db.user");
            $this->__databasePass = Config::get('db.pass');
            $this->__databaseName = Config::get('db.db');
            $this->__database = true;
        }
    }

    public function hash($data, $algo = null)
    {
        if ($algo == null) $algo = Config::get('app.hash');
        if ($algo == null || $algo == '') throw new Exception("No hash defined.");
        return hash($algo, $data);
    }

    public function hasDatabase()
    {
        return $this->__database;
    }

    public function generatePassword($len = 8)
    {

    }

    public function test()
    {
        echo "callStatic test successful."; exit;
    }

    public static function register()
    {
        return new Core();
    }

    public static function __callStatic($method, $arguments)
    {
        call_user_func_array(static::$ref->{$method}, $arguments);
    }
}