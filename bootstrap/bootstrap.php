<?php
session_name("lyanna_session");
session_start();
ob_start("showpage");

// Load classless helper functions
require_once(__VENDOR__ . 'Lyanna' . DIRECTORY_SEPARATOR . 'Support' . DIRECTORY_SEPARATOR . 'helpers.php');

// Load autoloader, then register
require_once(__VENDOR__ . DIRECTORY_SEPARATOR . "Autoload.php");
AutoLoader::register();

use Lyanna\Config;
use Lyanna\Core;
//use Lyanna\Database\Database;
use Lyanna\Foundation\Exception;
use Lyanna\Foundation\Output;
use Lyanna\Foundation\Request;
use Lyanna\Foundation\Router;

Exception::register();
Output::beginBuffer();
Config::load();

$app = Core::register();
$pdo = null;
$db = null;

function start()
{
    global $app, $db, $pdo;

    if (file_exists($path = __APP__ . "environment" . DIRECTORY_SEPARATOR . "start.php")) {
        require_once($path);
    }

    Router::handleURL(Request::getEnv("SERVER_NAME"), Request::getEnv("REQUEST_URI"));
}

function showpage($buffer)
{
    return \Lyanna\Foundation\MinifyHTML::minify($buffer);
}