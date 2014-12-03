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

class Result
    extends \Lyanna\Database\Result
{
    function __construct($result)
    {
        $this->_result = $result;
    }

    public function next()
    {
        $this->checkFetched();
        $this->_row = $this->_result->fetch_object();
        if ($this->_row)
            $this->_position++;
        else
            $this->_result->free();
    }

    public function rewind()
    {
        if ($this->_position > 0)
            throw new \Exception("mysqli result cannot be rewound for unbuffered queries.");
    }
} 