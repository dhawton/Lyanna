<?php
namespace Lyanna\Foundation;

class Redirect
{
    public static function to($url)
    {
        Output::clean();
        header("Location: $url");
    }
}