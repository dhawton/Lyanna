<?php
use \Lyanna\View\View;

class HomeController
{
    public static function getIndex()
    {
        return View::make('Home/Index');
    }
}