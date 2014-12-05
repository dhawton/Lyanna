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

namespace Lyanna;


class ORM
{
    public $app;
    public $columnCache;

    function __construct($app)
    {
        $this->app = $app;
    }

    public function extension($class, $model) {
        return new $class($this->app, $model);
    }

    public function get($name, $id = null)
    {
        $name = explode("_", $name);
        $name = array_map('ucfirst', $name);
        $name = implode("\\", $name);
        $model = "\\" . Config::get('app.namespace') . "\\Models";

        if ($id != null) {
            $model = $model->where($model->idField, $id)->find();
            $model->values(array($model->idField => $id));
        }
        return $model;
    }

    public function result($model, $dbresult, $with = array()) {
        return new \Lyanna\ORM\Result($this->app, $model, $dbresult, $with);
    }
} 