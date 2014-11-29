<?php
namespace Lyanna\Foundation;
class Request
{
    public static function getRequest($key, $sanitize = false)
    {
        if (isset($_REQUEST[$key]))
            if ($sanitize)
                return Safety::Sanitize($_REQUEST[$key]);
            else
                return $_REQUEST[$key];
        else
            return null;
    }

    public static function getPost($key, $sanitize = false)
    {
        if (isset($_POST[$key]))
            if ($sanitize)
                return Safety::Sanitize($_POST[$key]);
            else
                return $_POST[$key];
        else
            return null;
    }

    public static function getEnv($key)
    {
        if (isset($_SERVER[$key]))
            return $_SERVER[$key];
        else
            return null;
    }

    public static function getStep($step = 0)
    {
        $url = getEnv("REQUEST_URI");
        $url = substr($url, 1);
        $parts = explode("/",$url);
        return $parts[($step+1)];
    }
}