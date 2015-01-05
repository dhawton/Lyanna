<?php


namespace Lyanna\Foundation;


class Safety
{
    public static function Sanitize($string)
    {
        $search=array("\\","\0","\n","\r","\x1a","'",'"');
        $replace=array("\\\\","\\0","\\n","\\r","\Z","\'",'\"');
        return str_replace($search,$replace,$string);
    }
}