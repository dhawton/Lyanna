<?php
namespace Lyanna\View;

class View
{
    public static function make($path)
    {
        global $app;

        $file = __VIEW__ . $path . ".php";

        if (file_exists($file))
        {
            return require_once($file);
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