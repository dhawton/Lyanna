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

class Result
    extends \Lyanna\Database\Result
{
    function __construct($statement)
    {
        $this->_result = $statement;
    }

    public function next()
    {
        $this->checkFetched();
        $this->_row = $this->_result->fetchObject();
        if ($this->row)
            $this->_position++;
        else
            $this->_result->closeCursor();
    }

    public function rewind()
    {
        if ($this->_position > 0)
            throw new \Exception('PDO statement cannot be rewound for unbuffered queries');
    }
} 