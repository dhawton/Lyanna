<?php
/**
 * Created by PhpStorm.
 * User: Daniel
 * Date: 22/12/2014
 * Time: 18:00
 */

namespace Lyanna\View;

class Bundle {
    public static $scripts = array();
    public static $styles = array();

    public static function Scripts($data)
    {
        if ($data === null) return;

        if (is_array($data)) {
            foreach ($data as $script) {
                if (!in_array($script, static::$scripts))
                    static::$scripts[] = $script;
            }
        } else
            static::$scripts[] = $data;
    }

    public static function Styles($data)
    {
        if ($data === null) return;

        if (is_array($data)) {
            foreach ($data as $style) {
                if (!in_array($style, static::$styles))
                    static::$styles[] = $style;
            }
        } else
            static::$styles[] = $data;
    }
} 