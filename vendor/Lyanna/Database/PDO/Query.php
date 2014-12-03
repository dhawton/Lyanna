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

use Lyanna\Database\Expression;

class Query
    extends \Lyanna\Database\Query
{
    protected $_dbType;
    protected $_quote;

    function __construct($db, $type)
    {
        parent::__construct($db, strtolower($type));
        $this->_dbType = $this->_db->dbType;
        $this->_quote = ($this->_dbType == 'mysql' ? '`' : '"');
    }

    protected function quote($str)
    {
        return $this->_quote . $str . $this->_quote;
    }

    protected function subquery($query, &$params)
    {
        $query = $query->query();
        $params = array_merge($params, $query[1]);
        return "({$query[0]})";
    }

    public function escapeField($field, $prependTable = true)
    {
        if (is_object($field) && $field instanceof Expression)
            return $field->value;

        $field = explode('.', $field);
        if (count($field) == 1) {
            if (!$prependTable)
                return $this->quote($field[0]);

            array_unshift($field, $this->lastAlias());
        }
        $str = $this->quote($field[0]) . '.';
        if (trim($field[1]) == '*')
            return $str.'*';

        return $str . $this->quote($field[1]);
    }

    public function escapeTable($table, &$params)
    {
        $alias = null;
        if (is_array($table)) {
            $alias = $table[1];
            $table = $table[0];
        }

        if (is_string($table)) {
            $table = $this->quote($table);
            if ($alias != null)
                $table .= " AS " . $this->quote($alias);
            return $table;
        }

        if ($alias == null)
            $alias = $this->lastAlias();

        if ($table instanceof \Lyanna\Database\Query)
            return $this->subquery($table, $params) . " AS " . $this->quote($alias);

        if ($table instanceof Expression)
            return $table->value . " AS " . $this->quote($alias);

        throw new \Exception("Parameter type " . get_class($table) . " cannot be used as a table");
    }

    public function escapeValue($val, &$params)
    {
        if ($val instanceof Expression) {
            foreach ($val->params as $p)
                $params[] = $p;
            return $val->value;
        }

        if ($val instanceof \Lyanna\Database\Query)
            return $this->subquery($val, $params);

        $params[] = $val;
        return "?";
    }

    public function getConditionQuery($condition, &$params, $skipFirstOperator, $valueIsField = false)
    {
        if (isset($condition['field'])) {
            if ($valueIsField)
                $param = $this->escapeField($condition['value']);
            else
                $param = $this->escapeValue($condition['value'], $params);

            return $this->escapeField($condition['field'] . " " . $condition['operator'] . " " . $param);
        }

        if (isset($condition['logic']))
            return ($skipFirstOperator ? "" : strtoupper($condition['logic']) . ' ') .
                $this->getConditionQuery($condition['conditions'], $params, false, $valueIsField);

        $conds = '';
        $skip = $skipFirstOperator || (count($condition) > 1);
        foreach ($condition as $x) {
            $conds .= $this->getConditionQuery($x, $params, $skip, $valueIsField) . ' ';
            $skip = false;
        }

        if (count($condition) > 1 && !$skipFirstOperator)
            return "( $conds)";

        return $conds;
    }

    public function query()
    {
        $query = '';
        $params = array();

        if ($this->_type == "insert") {
            $query .= "INSERT INTO " . $this->escapeTable($this->_table, $params) . " ";
            if (empty($this->_data) && $this->_dbType == "pgsql")
                $query .= "DEFAULT VALUES ";
            else {
                if (isset($this->_data[0]) && is_array($this->_data[0])) {
                    $firstRow = true;
                    $arrColumns = array();
                    foreach ($this->_data as $row) {
                        $columns = '';
                        $values = '';
                        $first = true;
                        if ($firstRow) {
                            foreach ($row as $key => $val) {
                                if (!$first) { $values .= ', '; $columns .= ', '; }
                                else
                                    $first = false;

                                $columns .= $this->quote($key);
                                $arrColumns[] = $key;
                                $values .= $this->escapeValue($val, $params);
                            }
                            $query .= "($columns) VALUES($values)";
                        } else {
                            foreach ($arrColumns as $col) {
                                if (!$first)
                                    $values .= ", ";
                                else
                                    $first = false;

                                $values .= $this->escapeValue($row[$col], $params);
                            }
                            $query .= ", ($values)";
                        }
                        $firstRow = false;
                    }
                } else {
                    $columns = '';
                    $values = '';
                    $first = true;

                    foreach ($this->_data as $key => $val) {
                        if (!$first) { $values .= ', '; $columns .= ', '; }
                        else $first = false;
                        $columns .= $this->quote($key);
                        $values .= $this->escapeValue($val, $params);
                    }
                    $query .= "($columns) VALUES($values)";
                }
            }
        } else {
            if ($this->_type == "select") {
                $query .= "SELECT ";
                if ($this->_fields == null)
                    $query .= "* ";
                else {
                    $first = true;
                    foreach ($this->_fields as $f) {
                        if (!$first) $query .= ", ";
                        else $first = false;

                        if (is_array($f))
                            $query .= $this->escapeField($f[0]) . " AS " . $this->quote($f[1]) . " ";
                        else
                            $query .= $this->escapeField($f) . " ";
                    }
                }
                if (!empty($this->_table))
                    $query .= "FROM " . $this->escapeTable($this->_table, $params) . " ";
            } elseif ($this->_type == "count") {
                $query .= "SELECT COUNT(*) AS " . $this->quote("count") . " FROM " . $this->escapeTable($this->_table, $params) . " ";
            } elseif ($this->_type == "delete") {
                if ($this->_dbType != 'sqlite')
                {
                    if (!empty($this->_joins))
                        $query .= "DELETE {$this->lastAlias()}.* FROM {$this->escapeTable($this->_table, $params)} ";
                    else
                        $query .= "DELETE FROM {$this->escapeTable($this->_table, $params)} ";
                }
                else
                {
                    if (!empty($this->_joins))
                        throw new \Exception("SQLite doesn't support deleting a table with JOIN in the query");

                    $query .= "DELETE FROM {$this->escapeTable($this->_table, $params)} ";
                }
            } elseif ($this->_type == "update") {
                $query .= "UPDATE " . $this->escapeTable($this->_table, $params) . " ";
            }

            foreach ($this->_joins as $join) {
                $table = $this->escapeTable($join[0], $params);
                $query .= strtoupper($join[1]) . " JOIN $table ";
                if (!empty($join[2]))
                    $query .= "ON " . $this->getConditionQuery($join[2], $params, true, true) . " ";
            }

            if ($this->_type == "update") {
                $query .= "SET ";

                $first = true;
                foreach ($this->_data as $key => $val) {

                    if (!$first)
                        $query.=", ";
                    else
                        $first = false;

                    $query .= $this->quote($key) . " = " . $this->escape_value($val, $params);
                }
                $query .= " ";
            }

            if (!empty($this->_conditions))
                $query .= "WHERE " . $this->getConditionQuery($this->_conditions, $params, true) . " ";
            if (($this->_type == 'select' || $this->_type == 'count') && $this->_groupBy != null)
                $query .= "GROUP BY " . $this->escapeField($this->_groupBy, false) . " ";
            if (($this->_type == "select" || $this->_type == "count") && !empty($this->_having))
                $query .= "HAVING " . $this->getConditionQuery($this->_having, $params, true) . " ";

            if ($this->_type == "select" && !empty($this->_orderBy)) {
                $query .= "ORDER BY ";
                $first = true;

                foreach ($this->_orderBy as $order) {
                    if (!$first) $query .= ", ";
                    else $first = false;

                    $query .= $this->escapeField($order[0]) . " ";
                    if (isset($order[1]))
                        $query .= strtoupper($order[1]) . " ";
                }
            }

            if (count($this->_union) > 0 && ($this->_type == 'select')) {
                $query = "($query) ";
                foreach ($this->_union as $union) {
                    $query .= $union[1] ? "UNION ALL " : "UNION ";
                    if ($union[0] instanceof \Lyanna\Database\Query)
                        $query .= $this->subquery($union[0], $params);
                    elseif ($union[0] instanceof \Lyanna\Database\Expression)
                        $query .= "(" . $union[0]->value . ") ";
                    else
                        throw new \Exception("You can only use query builder instances or \$app->db->expression() for unions");
                }
            }

            if ($this->_type != "count") {
                if ($this->_limit != null)
                    $query .= "LIMIT " . $this->_limit;
                if ($this->_offset != null)
                    $query .= "OFFSET " . $this->_offset;
            }
        }

        return array($query, $params);
    }
} 