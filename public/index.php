<?php
/****************************************************
 * Copyright 2014 Daniel A. Hawton
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
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