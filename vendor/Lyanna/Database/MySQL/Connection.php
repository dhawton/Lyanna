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

namespace Lyanna\Database\MySQL;

use Lyanna\Config;
use Lyanna\Database\Query;
use Lyanna\Database\Result;

class Connection
    extends \Lyanna\Database\Connection
{
    // mysqli connection object
    public $conn;

    public $dbType = "mysql";

    function __construct($app, $config)
    {
        parent::__construct($app, $config);

        $this->conn = mysqli_connect(
            Config::get("db.$config.host", "localhost"),
            Config::get("db.$config.user", "localhost"),
            Config::get("db.$config.pass", "localhost"),
            Config::get("db.$config.database", "localhost")
        );

        $this->conn->set_charset("utf8");
    }

    public function execute($query, $params = array())
    {
        $cursor = $this->conn->prepare($query);
        if (!$cursor)
            throw new \Exception("Database error: {$this->conn->error} in query $query");

        $types = '';
        $bind = array();
        $refs = array();

        if (!empty($params)) {
            foreach ($params as $key => $param) {
                $refs[$key] = is_array($param) ? $param[0] : $param;
                $bind[] = &$refs[$key];
                $types .= is_array($param) ? $param[1] : 's';
            }
            array_unshift($bind, $types);

            call_user_func_array(array($cursor, 'bind_param'), $bind);
        }
    }

    public function insertId()
    {
        return $this->conn->insert_id;
    }

    public function listColumns($table)
    {
        $columns = array();
        $tableDesc = $this->execute("DESCRIBE `$table`");
        if (!$tableDesc->valid())
            throw new \Exception ("Table '$table' does not exist");

        foreach ($tableDesc as $column)
            $columns[] = $column->Field;

        return $columns;
    }

    public function query($type)
    {
        $this->app->db->queryDriver("MySQL", $this, $type);
    }
} 