<?php
namespace Lyanna\Foundation;

class Output {
    public static function beginBuffer()
    {
        ob_start();
    }

    public static function clean()
    {
        ob_clean();
    }

    public static function forceFront($line)
    {
        $data = ob_get_contents();
        ob_clean();
        ob_start();
        echo $line;
        echo $data;
    }

    public static function flush()
    {
        ob_flush();
    }
}