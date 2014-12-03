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

namespace Lyanna\Database;


abstract class Connection
{
    public $app;

    function __construct($app)
    {
        $this->app = $app;
    }

    public abstract function execute($query, $params = array());
    public abstract function insertId();
    public abstract function listColumns($table);
    public abstract function query($type);

    public function expression($value, $params = array())
    {
        return $this->app->db->expression($value, $params);
    }

    public function namedQuery($query, $params = array())
    {
        $bind = array();
        preg_match_all('#:(\w+)#is', $query, $matches, PREG_SET_ORDER);
        foreach ($matches as $match)
        {
            if (isset($params[$match[1]]))
            {
                $query = preg_replace("#{$match[0]}#", '?', $query, 1);
                $bind[] = $params[$match[1]];
            }
        }
        return $this->execute($query, $bind);
    }
}