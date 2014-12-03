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

namespace Lyanna;
use Lyanna\Config;

class Database
{
    public $app;

    const DEFAULT_CONNECTION = 'default';

    protected $dbInstances = array();

    function __construct($app)
    {
        $this->app = $app;
    }

    public function expression($value, $params = array())
    {
        return new Database\Expression($value, $params);
    }

    public function get($config = self::DEFAULT_CONNECTION)
    {
        if (!isset($this->dbInstances[$config]))
        {
            $driver = "\\Lyanna\\Database\\" . Config::get("db.$config.driver") . "\\Connection";
            $this->dbInstances[$config] = new $driver($this->app, $config);
        }
        return $this->dbInstances[$config];
    }

    public function insertId($config = self::DEFAULT_CONNECTION)
    {
        return $this->get($config)->insertId();
    }

    public function listColumns($table, $config = self::DEFAULT_CONNECTION)
    {
        return $this->get($config)->listColumns($table);
    }

    public function query($type, $config = self::DEFAULT_CONNECTION)
    {
        return $this->get($config)->query($type);
    }

    public function queryDriver($driver, $db, $type)
    {
        $driver = "\\Lyanna\\Database\\$driver\\Query";
        return new $driver($db, $type);
    }

    public function resultDriver($driver, $cursor)
    {
        $driver = "\\Lyanna\\Database\\$driver\\Result";
        return new $driver($cursor);
    }
}