<?php
namespace Lyanna\Foundation;

use Lyanna\Config;

class URL
{
    public static function makeContent($content)
    {
        if (Request::getEnv("REQUEST_SCHEME") == "https") { $scheme = "https"; }
        else { $scheme = "http"; }

        return $scheme . "://" . Config::get('app.URL') . "/Content/$content";
    }
}