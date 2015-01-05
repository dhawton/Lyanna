<?php
namespace Lyanna\Foundation;

use Lyanna\Config;

class Router
{
    protected static $routes;
    protected static $tempRule;

    function __construct($app) { $this->app = $app; }

    protected static function rule($str) {
        $str = $str[0];
        $regexp = '[a-zA-Z0-9\-\._]+';
        if (is_array(static::$tempRule))
            $regexp = arr(static::$tempRule[1], str_replace(array('<','>'), '', $str), $regexp);
        return '(?P'.$str.$regexp. ')';
    }

    public static function parseURL($servername, $url)
    {
        $ret = array();
        if ($url[0] == '/') $url = substr($url, 1);
        $parts = explode("/", $url, 3);
        if (isset($parts[0])) { $ret['controller'] = $parts[0]; }
        else { $ret['controller'] = ""; }
        if (isset($parts[1])) { $ret['action'] = $parts[1]; }
        else {$ret['action'] = ""; }
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

    public static function processRoutes()
    {
        if (is_array(Config::get('routes'))) {
            $configRoutes = Config::get('routes');
            foreach ($configRoutes as $name => $rule) {
                $route = new Def\Route($name, $rule[0], $rule[1], arr($rule, 2, null));
                static::$routes[$route->name] = $route;
            }
        }
    }

    public static function handleURL($servername, $url)
    {
        $cont = true;
        $data = array();
        $params = array();
        // Check against routing table first.
        if (is_array(Config::get('routes'))) {
            static::processRoutes();
            $matched = false;
            $method = Request::getEnv('REQUEST_METHOD');
            foreach (static::$routes as $name => $route) {
                if ($route->methods != null && !in_array($method, $route->methods)) continue;

                $rule = $route->rule;
                $pattern = is_array($rule) ? $rule[0] : $rule;
                $pattern = str_replace(')', ')?', $pattern);
                static::$tempRule = $rule;
                $pattern = preg_replace_callback('/<.*?>/', 'static::rule', $pattern);
                preg_match('#^'.$pattern. '/?#',$url,$match);
                if (!empty($match[0])) {
                    $matched = $name;
                    foreach ($match as $key => $val)
                        if (!is_numeric($key))
                            $data[$key] = $val;
                    break;
                }
            }
            if ($matched !== false) {
                $route = static::$routes[$matched];
                $params = array_merge($route->defaults, $data);

                if (!strpos($params['controller'], "Controller")) { $params['controller'] .= "Controller"; }
                $cont = false;
            }
            $parts['parameters'] = $params;
        }

        if ($cont !== false) {
            $parts = static::parseURL($servername, $url);
            if (!$parts['controller']) { $parts['controller'] = "Home"; }
            if (!$parts['action']) { $parts['action'] = "Index"; }

            $controller = $parts['controller'] . "Controller";
            $action = strtolower($_SERVER['REQUEST_METHOD']) . $parts['action'];
            $params = $parts;
            $params['controller'] = $controller;
            $params['action'] = $action;
        }
        $path = __APP__ . "controllers" . DIRECTORY_SEPARATOR .
            ((isset($parts['subdomain']))? $parts['subdomain'] . DIRECTORY_SEPARATOR : "") . $params['controller'] . ".php";

        if (!class_exists($params['controller'])) {
            if (file_exists($path))
                require_once($path);
            else
                Exception::throwHttpError(401,"Not implemented");
        }

        if (is_callable($params['controller'] . "::accessCheck"))
            call_user_func($params['controller']."::accessCheck", $parts);

        if (!is_callable($params['controller']."::".$params['action']))
            Exception::throwHttpError(404,"Not found ".$params['controller']."::".$params['action']);

        call_user_func($params['controller']."::".$params['action'], $parts['parameters']);
    }
}