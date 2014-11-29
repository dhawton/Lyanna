<?php
use \Lyanna\View\View;
use \vattrain\Auth\Auth;

class HomeController
{
    public static function getIndex()
    {
        if (!Auth::Check())
        return View::make('Home/Index');
    }
}