<?php
namespace Lyanna;

class Config {
    protected static $config = array();
    protected static $loaded = false;

    public static function get($key, $default = null)
    {
        static::load();
        return array_get(static::$config, $key, $default);
    }

    public static function load()
    {
        if (static::$loaded)
            return null;

        if (file_exists($path = __APP__ . 'config' . DIRECTORY_SEPARATOR . "config.php")) {
            static::$config = require_once($path);
            static::$loaded = true;
        } else {
            throw new \Exception("Unable to load configuration");
        }
    }
}