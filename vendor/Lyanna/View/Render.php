<?php
/**
 * Created by PhpStorm.
 * User: Daniel
 * Date: 22/12/2014
 * Time: 18:03
 */

namespace Lyanna\View;

use Lyanna\Config;

class Render {
    public static function Scripts()
    {
        $bundles = Bundle::$scripts;
        foreach ($bundles as $bundle)
        {
            echo "<script type=\"text/javascript\" src=\"/assets/bundles/" . md5($bundle) . ".js" . ((Config::get("bundles.gzip", true) == true) ? ".gz" : "") . "\"></script>";
        }
    }

    public static function Styles()
    {
        $bundles = Bundle::$styles;
        foreach ($bundles as $bundle)
        {
            echo "<link rel=\"stylesheet\" href=\"/assets/bundles/" . md5($bundle) . ".css" . ((Config::get("bundles.gzip", true) == true) ? ".gz" : "") . "\">";
        }
    }
} 