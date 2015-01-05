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

namespace Lyanna\Database\PDO;
use Lyanna\Database\Result;
use Lyanna\Config;

class Connection
    extends \Lyanna\Database\Connection
{
    public $app;
    public $conn;
    public $dbType;

    function __construct($app, $config)
    {
        parent::__construct($app, $config);

        $this->conn = new \PDO(
            Config::get("db.$config.connection"),
            Config::get("db.$config.user",''),
            Config::get("db.$config.pass",'')
        );
        $this->conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->dbType = strtolower(str_replace('PDO_','', $this->conn->getAttribute(\PDO::ATTR_DRIVER_NAME)));
        if ($this->dbType != 'sqlite')
            $this->conn->exec("SET NAMES 'utf8'");
    }

    public function execute($query, $params = array())
    {
        $cursor = $this->conn->prepare($query);
        if (!$cursor->execute($params)) {
            $error = $cursor->errorInfo();
            throw new \Exception("Database error {$error[2]} in query: $query");
        }

        return $this->app->db->resultDriver("PDO", $cursor);
    }

    public function insertId()
    {
        if ($this->dbType == 'pgsql')
            return $this->execute("SELECT lastval() as id")->current()->id;
        return $this->conn->lastInsertId();
    }

    public function listColumns($table)
    {
        $columns = array();
        if ($this->dbType == 'mysql') {
            $tableDesc = $this->execute("DESCRIBE `$table`");
            foreach ($tableDesc as $column)
                $columns[] = $column->Field;
        }
        if ($this->dbType == 'pgsql') {
            $tableDesc = $this->execute("SELECT column_name FROM information_schema.columns WHERE table_name='$table' AND table_catalog=current_database();");
            foreach ($tableDesc as $column)
                $columns[] = $column->column_name;
        }
        if ($this->dbType == 'sqlite') {
            $tableDesc = $this->execute("PRAGMA table_info('$table')");
            foreach ($tableDesc as $column)
                $columns[] = $column->name;
        }

        return $columns;
    }

    public function query($type)
    {
        return $this->app->db->queryDriver("PDO", $this, $type);
    }
} 