<?php
namespace Lyanna\View;

class View
{
    public static function make($path = null)
    {
        // Auto fill path :)
        if ($path == null) {
            $trace = debug_backtrace();
            $caller = preg_replace('/^(get|post|push|delete)/', '', $trace[1]['function'], 1);
            $class = preg_replace('/Controller$/', '', $trace[1]['class']);
            if ($class && $caller) {
                $path = $class . DIRECTORY_SEPARATOR . $caller;
            }
        }

        $file = __VIEW__ . $path . ".php";

        if (file_exists($file))
        {
            return require_once($file);
        } else {
            throw new \Exception("Unable to make view as view does not exist!");
        }
    }

    public static function showHeader($header = "header")
    {
        global $app;
        static::make("layouts/$header");
    }

    public static function showFooter($footer = "footer")
    {
        global $app;
        static::make("layouts/$footer");
    }
}