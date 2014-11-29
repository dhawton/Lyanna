<?php
class AutoLoader
{
    private static $registered = false;

    public static function register()
    {
        if (static::$registered == false) {
            spl_autoload_register('AutoLoader::loader');
            static::$registered = true;
        }
    }

    public static function unregister()
    {
        if (static::$registered) {
            spl_autoload_unregister('AutoLoader::loader');
        }
    }

    public static function normalizeClass($class)
    {
        if ($class[0] == '\\')
            $class = substr($class, 1);

        return str_replace(array('\\','_'), DIRECTORY_SEPARATOR, $class);
    }

    public static function loader($reqClass)
    {
        $class = static::normalizeClass($reqClass);

        if (file_exists($path = __DIR__ . DIRECTORY_SEPARATOR . $class . ".php"))
            require_once($path);
        /*else
            throw new \Exception("Class required but not found, $class at $path"); */
    }
}