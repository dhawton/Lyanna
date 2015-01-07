<?php


namespace Lyanna;


class Core
{
    protected $instanceClasses = array();
    protected $instances = array();
    protected $modules = array(
        'db' => '\Lyanna\Database\Database',
        'orm' => '\Lyanna\ORM\ORM'
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
        if (!isset($this->globals[$key])) return null;
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
        if (is_array(Config::get('bundles')))
            $this->checkBundles();
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

    public function checkBundles()
    {
        if (!file_exists(__PUBLIC__ . "assets" . DIRECTORY_SEPARATOR . "bundles"))
            mkdir(__PUBLIC__ . "assets" . DIRECTORY_SEPARATOR . "bundles", 0755);

        $pathBundles = __PUBLIC__ . "assets" . DIRECTORY_SEPARATOR . "bundles" . DIRECTORY_SEPARATOR;

        $jsbundles = Config::get('bundles.js');
        $gzip = Config::get('bundles.gzip', true);
        foreach ($jsbundles as $bname => $b) {
            if (file_exists($pathBundles . md5($bname) . ".js" . ($gzip) ? ".gz" : "")) {
                foreach ($b as $jscript) {
                    if (filemtime(__PUBLIC__ . $jscript) > filemtime($pathBundles . md5($bname) . ".js" . ($gzip) ? ".gz" : "")) {
                        $this->buildJSBundle($bname, $b);
                        continue;
                    }
                }
            } else {
                $this->buildJSBundle($bname, $b);
            }
        }

        $cssbundles = Config::get('bundles.css');
        foreach ($cssbundles as $bname => $b) {
            if (file_exists($pathBundles . md5($bname) . ".css" . ($gzip) ? ".gz" : "")) {
                foreach ($b as $css) {
                    if (filemtime(__PUBLIC__ . $css) > filemtime($pathBundles . md5($bname) . ".css" . ($gzip) ? ".gz" : "")) {
                        $this->buildCSSBundle($bname, $b);
                    }
                }
            } else {
                $this->buildCSSBundle($bname, $b);
            }
        }
    }

    public function buildJSBundle($name, $bundle)
    {
        $bigscript = null;
        $gzip = Config::get("bundles.gzip", true);
        if ($gzip)
            $fh = gzopen(__PUBLIC__ . "assets" . DIRECTORY_SEPARATOR . "bundles" . DIRECTORY_SEPARATOR . md5($name) . ".js.gz", "wb9");
        else
            $fh = fopen(__PUBLIC__ . "assets" . DIRECTORY_SEPARATOR . "bundles" . DIRECTORY_SEPARATOR . md5($name) . ".js", "w");

        foreach ($bundle as $jscript) {
            $bigscript .= file_get_contents(__PUBLIC__ . ltrim($jscript, "\\/"));
        }

        if ($gzip)
            gzwrite($fh, \JShrink\Minifier::minify($bigscript));
        else
            fwrite($fh, \JShrink\Minifier::minify($bigscript));

        if ($gzip)
            gzclose($fh);
        else
            fclose($fh);
        if ($gzip)
            chmod(__PUBLIC__ . "assets" . DIRECTORY_SEPARATOR . "bundles" . DIRECTORY_SEPARATOR . md5($name) . ".js.gz", 0755);
        else
            chmod(__PUBLIC__ . "assets" . DIRECTORY_SEPARATOR . "bundles" . DIRECTORY_SEPARATOR . md5($name) . ".js", 0755);
    }

    public function buildCSSBundle ($name, $bundle)
    {
        $bigstyle = null;
        $gzip = Config::get("bundles.gzip", true);

        if ($gzip)
            $fh = gzopen(__PUBLIC__ . "assets" . DIRECTORY_SEPARATOR . "bundles" . DIRECTORY_SEPARATOR . md5($name) . ".css.gz", "wb9");
        else
            $fh = fopen(__PUBLIC__ . "assets" . DIRECTORY_SEPARATOR . "bundles" . DIRECTORY_SEPARATOR . md5($name) . ".css", "w");

        foreach ($bundle as $stylesheet) {
            $bigstyle .= file_get_contents(__PUBLIC__ . ltrim($stylesheet, "\\/"));
        }

        $bigstyle = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $bigstyle);
        $bigstyle = str_replace(': ', ':', $bigstyle);
        $bigstyle = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $bigstyle);

        if ($gzip)
            gzwrite($fh, $bigstyle);
        else
            fwrite($fh, $bigstyle);

        if ($gzip)
            gzclose($fh);
        else
            fclose($fh);

        if ($gzip)
            chmod(__PUBLIC__ . "assets" . DIRECTORY_SEPARATOR . "bundles" . DIRECTORY_SEPARATOR . md5($name) . ".css.gz", 0755);
        else
            chmod(__PUBLIC__ . "assets" . DIRECTORY_SEPARATOR . "bundles" . DIRECTORY_SEPARATOR . md5($name) . ".css", 0755);
    }
}