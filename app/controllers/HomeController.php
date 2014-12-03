<?php
use \Lyanna\View\View;

class HomeController
{
    public static function getIndex()
    {
        if (!Auth::Check())
        return View::make('Home/Index');
    }
}