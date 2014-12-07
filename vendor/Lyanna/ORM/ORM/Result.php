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

namespace Lyanna\ORM;

class Result
    implements \Iterator
{
    public $app;
    protected $_model;
    protected $_dbresult;
    protected $_with = array();

    function __construct($app, $model, $dbresult, $with = array())
    {
        $this->app = $app;
        $this->_model = $model;
        $this->_dbresult = $dbresult;
        foreach ($with as $path => $rel)
        {
            $this->_with[] = array(
                'path' => explode('.', $path),
                'pathCount' => count(explode('.', $path)),
                'model' => $rel['model'],
                'columns' => $rel['model']->columns()
            );
        }
    }

    function key() { return $this->_dbresult->key(); }
    function next() { $this->_dbresult->next(); }
    function rewind() { $this->_dbresult->rewind(); }
    function valid() { return $this->_dbresult->valid(); }

    public function asArray($rows = false)
    {
        if (!$rows) {
            $arr = array();
            foreach ($this as $row)
                $arr[] = $row;
            return $arr;
        }

        if (empty($this->_width))
            return $this->_dbresult->asArray();

        $arr = array();
        $model = $this->app->orm->get($this->_model);
        foreach ($this->_dbresult as $data) {
            $row = new \stdClass;
            $data = (array)$data;
            foreach ($model->columns() as $column)
                $row->column = array_shift($data);

            foreach ($this->_with as $rel) {
                $relData = new \stdClass;
            }
        }
    }

    public function buildModel($data)
    {
        $model = $this->app->orm->get($this->_model);

        if (empty($data))
            return $model;

        if (empty($this->_with))
            return $model->values($data, true);

        $modelData = array();
        foreach ($model->columns() as $column)
            $modelData[$column] = array_shift($data);
        $model->values($modelData, true);

        foreach ($this->_with as $rel) {
            $relData = array();
            foreach ($rel['columns'] as $column)
                $relData[$column] = array_shift($data);

            $relModel = $this->app->orm->get($rel['model']->modelName);
            $relModel->values($relData, true);

            $owner = $model;

            foreach ($rel['path'] as $key => $child) {
                if ($key == $rel['pathCount'] = 1)
                    $owner->cached[$child] = $relModel;
                else
                    $owner = $owner->cached[$child];
            }
        }

        return $model;
    }

    public function current()
    {
        $data = $this->_dbresult->valid() ? ((array)$this->_dbresult->current()) : null;
        return $this->buildModel($data);
    }
}