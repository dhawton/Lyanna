<?php
namespace Lyanna\Foundation;

use Lyanna\Config;

class Exception {
    public static function register()
    {
        set_exception_handler('\Lyanna\Foundation\Exception::handler');
    }
    public static function handler($ex)
    {
        Output::clean();
        require_once(__APP__ .DIRECTORY_SEPARATOR."resources".DIRECTORY_SEPARATOR."exception.php");
    }
    public static function throwHttpError($code = 400,$message=null)
    {
        Output::clean();
        if (file_exists($path=__APP__.DIRECTORY_SEPARATOR."resources".DIRECTORY_SEPARATOR."$code.php"))
            require_once($path);
        else
            require_once(__APP__.DIRECTORY_SEPARATOR."resources".DIRECTORY_SEPARATOR."exception.php");
        exit;
    }
    public static function throwWarning($message)
    {
        if(Config::get('app.debug') == true)
            if (file_exists($path = __APP__.DIRECTORY_SEPARATOR."resources".DIRECTORY_SEPARATOR."warning.php"))
                include($path);
    }
}