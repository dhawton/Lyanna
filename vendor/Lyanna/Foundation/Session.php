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

namespace Lyanna\Foundation;

class Session
{
    protected $app;

    function __construct($app)
    {
        $this->app = $app;
    }

    public function check()
    {
        if (!session_id())
            session_start();
    }

    public function get($key = null, $default = null)
    {
        $this->check();
        if ($key === null)
            return $_SESSION[];

        if (isset($_SESSION[$key]))
            return $_SESSION[$key];
        else
            return $default;
    }

    public function remove($key)
    {
        $this->check();

        if (!isset($_SESSION[$key]))
            return;

        $var = $_SESSION[$key];
        unset($_SESSION[$key], $var);
    }

    public function reset()
    {
        $this->check();
        $_SESSION = array();
    }

    public function set($key, $value)
    {
        $this->check();
        $_SESSION[$key] = $value;
    }
} 