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


abstract class Result
    implements \Iterator
{
    protected $_position = -1;
    protected $_result;
    protected $_row;
    protected $_fetched = false;

    public function asArray()
    {
        $arr = array();
        foreach ($this as $row)
            $arr[] = $row;
        return $arr;
    }

    public function checkFetched()
    {
        if (!$this->_fetched) {
            $this->_fetched = true;
            $this->next();
        }
    }

    public function current()
    {
        $this->checkFetched();
        return $this->_row;
    }

    public function get($column)
    {
        if ($this->valid() && isset($this->_row->$column))
            return $this->_row->$column;

        return null;
    }

    public function key()
    {
        $this->checkFetched();
        return $this->_position;
    }

    public function result()
    {
        return $this->_result;
    }

    public function valid()
    {
        $this->checkFetched();
        return $this->_row != null;
    }
}