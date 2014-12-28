<?php
namespace Lyanna\Foundation;

use Lyanna\Config;

class Router
{
    public static function parseURL($servername, $url)
    {
        $ret = array();
        if ($url[0] == '/') $url = substr($url, 1);
        $parts = explode("/", $url, 3);
        if (isset($parts[0])) { $ret['controller'] = $parts[0]; }
        else { $ret['controller'] = ""; }
        if (isset($parts[1])) { $ret['method'] = $parts[1]; }
        else {$ret['method'] = ""; }
        if (isset($parts[2])) {
            if (strpos($parts[2], '/')) {
                $ret['parameters'] = explode('/', $parts[2]);
            } else {
                $ret['parameters'] = $parts[2];
            }
        } else {
            $ret['parameters'] = null;
        }

        if (preg_match('/^([^w]+)\.' . Config::get('app.URL') . '$/', $servername, $match)) {
            $ret['subdomain'] = str_replace('.', DIRECTORY_SEPARATOR, $match);
        } else { $ret['subdomain'] = ""; }

        return $ret;
    }

    public static function handleURL($servername, $url)
    {
        $parts = static::parseURL($servername, $url);
        if (!$parts['controller']) { $parts['controller'] = "Home"; }
        if (!$parts['method']) { $parts['method'] = "Index"; }

        $controller = $parts['controller'] . "Controller";
        $method = strtolower($_SERVER['REQUEST_METHOD']) . $parts['method'];

        $path = __APP__ . "controllers" . DIRECTORY_SEPARATOR .
            (($parts['subdomain'])? $parts['subdomain'] . DIRECTORY_SEPARATOR : "") . $controller . ".php";

        if (!class_exists($controller)) {
            if (file_exists($path))
                require_once($path);
            else
                Exception::throwHttpError(401,"Not implemented");
        }

        if (is_callable($controller . "::accessCheck"))
            call_user_func($controller."::accessCheck", $parts);

        if (!is_callable($controller."::".$method))
            Exception::throwHttpError(404,"Not found $controller::$method");

        call_user_func($controller."::".$method, $parts['parameters']);
    }
}