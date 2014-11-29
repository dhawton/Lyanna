<?php
/****************************************************
 * Lyanna Framework - by Daniel A. Hawton
 *
 * (C) 2014 Daniel A. Hawton <daniel@hawton.com>
 *
 * (INSERT LICENSE TEXT HERE)
 */

// Setup APP constant root paths
define("__APP__", __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR);
define("__VENDOR__", __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR);
define("__PUBLIC__", __DIR__ . DIRECTORY_SEPARATOR);
define("__VIEW__", __APP__ . "views" . DIRECTORY_SEPARATOR);

// Load boostrap
require_once(__PUBLIC__ . ".." . DIRECTORY_SEPARATOR . "bootstrap" . DIRECTORY_SEPARATOR . "bootstrap.php");

// Begin application
start();