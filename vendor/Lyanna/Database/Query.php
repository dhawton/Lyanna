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

abstract class Query
{
    protected $_conditions = array();
    protected $_table;
    protected $_fields;
    protected $_data;
    protected $_type;
    protected $_joins = array();
    protected $_limit;
    protected $_offset;
    protected $_orderBy = array();
    protected $_db;
    protected $_having = array();
    protected $_groupBy;
    protected $_alias = null;
    protected $methods = array(
        'data' => 'array',
        'limit' => array('integer', 'NULL'),
        'offset' => array('integer', 'NULL'),
        'group_by' => array('string', 'NULL'),
        'type' => 'string'
    );
    protected $_union = array();

    function __call($method, $args)
    {
        if (isset($this->methods[$method])) {
            $property = "_$method";
            if (empty($args))
                return $this->$property;

            $val = $args[0];
            if (is_numeric($val))
                $val = (int)$val;

            $allowedTypes = $this->methods[$method];
            if (!is_array($allowedTypes))
                $allowedTypes = array($allowedTypes);

            if (!in_array(gettype($val), $allowedTypes)) {
                if (!($val !== null && $val instanceof \Lyanna\Database\Expression))
                    throw new \Exception("Method '$method' only accepts values of type: '" . implode(' or ', $allowedTypes), " but $val was passed");
            }
            $this->$property = $val;
            return $this;
        }
        throw new \Exception("Method '$method' not defined.");
    }

    function __construct($db, $type)
    {
        $this->_db = $db;
        $this->_type = $type;
    }

    public function addAlias()
    {
        if ($this->_alias === null)
            $this->_alias = 0;
        else
            $this->_alias++;

        return $this->lastAlias();
    }

    public function execute()
    {
        $query = $this->query();
        $result = $this->_db->execute($query[0], $query[1]);
        if ($this->_type == 'count')
            return $result->get('count');
        return $result;
    }

    public function fields()
    {
        $p = func_get_args();
        if (empty($p))
            return $this->_fields;
        else
            $this->_fields = $p;

        return $this;
    }

    public function having()
    {
        $p = func_get_args();
        $condition = $this->getConditionPart($p);
        $this->_having = array_merge($this->_having, array($condition));
        return $this;
    }

    public function join($table, $conditions, $type = 'left')
    {
        $this->_joins[] = array($table, $type, $this->getConditionPart($conditions));
        return $this;
    }

    public function lastAlias()
    {
        if ($this->_alias == null) {
            if (is_array($this->_table))
                return $this->_table[1];

            return $this->_table;
        }

        return 'a' . $this->_alias;
    }

    public function orderBy($column, $direction = 'ASC')
    {
        $direction = strtoupper($direction);
        if ($direction != 'DESC' && $direction != 'ASC')
            throw new \Exception("Invalid sorting direction $direction passed");
        $this->_orderBy[] = array($column, $dir);
        return $this;
    }

    public function table($table = null, $alias = null)
    {
        if ($table == null)
            return is_array($this->_table) ? $this->_table[1] : $this->_table;

        if (!is_string($table) && $alias == null)
            $alias = $this->addAlias();

        $this->_table = $alias == null ? $table : array($table, $alias);

        return $this;
    }

    public function union($query, $all = true)
    {
        $this->_union[] = array($query, $all);
        return $this;
    }

    public function where()
    {
        $p = func_get_args();
        $condition = $this->getConditionPart($p);
        $this->_conditions = array_merge($this->_conditions, array($condition));

        return $this;
    }

    public abstract function query();


    private function getConditionPart($p)
    {
        if (is_string($p[0]) && (strtolower($p[0]) == 'or' || strtolower($p[0]) == 'and') && isset($p[1]) && is_array($p[1]))
        {
            $cond = $this->getConditionPart($p[1]);
            $cond['logic'] = strtolower($p[0]);
            return $cond;
        }

        if (is_array($p[0]))
        {
            if (count($p) == 1)
            {
                return $this->getConditionPart($p[0]);
            }
            $conds = array();
            foreach ($p as $q)
            {
                $conds[] = $this->getConditionPart($q);
            }
            if (count($conds) == 1)
            {
                return $conds;
            }
            return array('logic' => 'and', 'conditions' => $conds);
        }

        if ((is_string($p[0]) || $p[0] instanceof Expression) && isset($p[1]))
        {
            if (is_string($p[0]) && strpos($p[0], '.') === false)
            {
                $p[0] = $this->lastAlias().'.'.$p[0];
            }
            return array(
                'logic' => 'and',
                'conditions' => array(
                    'field' => $p[0],
                    'operator' => isset($p[2]) ? $p[1] : '=',
                    'value' => isset($p[2]) ? $p[2] : $p[1]
                )
            );
        }

        throw new \Exception('Incorrect conditional statement passed');
    }
}