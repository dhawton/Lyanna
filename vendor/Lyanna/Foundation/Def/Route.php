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

namespace Lyanna\Foundation\Def;


class Route
{
    public $name;
    public $rule;
    public $defaults;
    public $methods;
    private static $routes = array();

    function __construct($name, $rule, $defaults, $methods = null)
    {
        $this->name = $name;
        $this->rule = $rule;
        $this->defaults = $defaults;
        if ($methods != null) {
            if (is_string($methods))
                $methods = array($methods);
            $methods = array_map('strtoupper', $methods);
        }
        $this->methods = $methods;
    }
} 