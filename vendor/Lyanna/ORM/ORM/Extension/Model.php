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

namespace Lyanna\ORM\Extension;
use Lyanna\Config;

class Model
{
    public $app;
    public $conn;
    public $table;
    public $connection = "default";
    public $idField = "id";
    public $query;
    public $modelName;
    public $cached = array();
    protected $belongsTo;
    protected $db;
    protected $extensions = array();
    protected $extensionsInstances = array();
    protected $hasOne;
    protected $hasMany;
    protected $_loaded = array();
    protected $_row = array();
    protected $_with = array();
    protected static $_columnCache = array();

    function __call($method, $arguments)
    {
        if (!in_array($method, array('limit','offset','orderBy', 'where')))
            throw new \Exception("Method $method doesn't exist on " . get_class($this));

        $res = call_user_func_array(array($this->query, $method), $arguments);
        if (empty($arguments))
            return $res;

        return $this;
    }
    function __construct($app)
    {
        $this->app = $app;
        $this->conn = $app->db->get($this->connection);
        $this->query = $this->conn->query('select');
        $this->modelName = strtolower(get_class($this));
        $this->modelName = str_ireplace("\\" . Config::get('app.namespace') . "\\Model\\", '', $this->modelName);

        if ($this->table == null)
            $this->table = str_replace("\\","_",$this->modelName);

        $this->query->table($this->table);

        foreach(array('belongsTo','hasOne','hasMany') as $rels) {
            $normalized = array();
            foreach ($this->$rels as $key => $rel) {
                if (!is_array($rel)) {
                    $key = $rel;
                    $rel = array();
                }
                $normalized[$key] = $rel;
                if (!isset($rel['model']))
                    $normalized[$key]['key'] = $this->modelKey($rels != 'belongsTo' ? $this->modelName : $rel['model']);

                if ($rels == 'hasMany' && isset($rel['through']))
                    if (!isset($rel['foreignKey']))
                        $normalized[$key]['foreignKey'] = $this->modelKey($rel['model']);

                $normalized[$key]['name'] = $key;
            }

            $this->$rels = $normalized;
        }
    }
    function __get($column)
    {
        if (array_key_exists($column, $this->_row))
            return $this->_row[$column];

        if (array_key_exists($column, $this->cached))
            return $this->cached[$column];

        if (($val = $this->get($column)) !== null)
        {
            $this->cached[$column] = $val;
            return $val;
        }

        if (array_key_exists($column, $this->extensionsInstances))
        {
            return $this->extensionsInstances[$column];
        }

        if (array_key_exists($column, $this->extensions))
        {
            return $this->extensionsInstances[$column] =
                $this->app->orm->extension($this->extensions[$column], $this);
        }

        $relations = array_merge($this->hasOne, $this->hasMany, $this->belongsTo);
        if ($target = arr($relations, $column, false))
        {
            $model = $this->app->orm->get($target['model']);
            $model->query = clone $this->query;
            if ($this->loaded())
            {
                $model->query->where($this->idField, $this->_row[$this->idField]);
            }
            if ($target['type'] == 'hasMany' && isset($target['through']))
            {
                $lastAlias = $model->query->lastAlias();
                $throughAlias = $model->query->addAlias();
                $newAlias = $model->query->addAlias();
                $model->query->join(array($target['through'], $throughAlias), array(
                    $lastAlias.'.'.$this->id_field,$throughAlias.'.'.$target['key']), 'inner');
                $model->query->join(array($model->table, $newAlias), array($throughAlias.'.'.$target['foreignKey'],
                    $newAlias.'.'.$model->idField), 'inner');
            }
            else
            {
                $lastAlias = $model->query->lastAlias();
                $newAlias = $model->query->addAlias();
                if ($target['type'] == 'belongsTo')
                    $model->query->join(array($model->table, $newAlias), array($lastAlias.'.'.$target['key'],$newAlias.'.'.$model->idField), 'inner');
                else
                    $model->query->join(array($model->table, $newAlias), array(
                        $lastAlias.'.'.$this->idField,$newAlias.'.'.$target['key']), 'inner');
            }
            $model->query->fields("$newAlias.*");
            if ($target['type'] != 'hasMany' && $this->loaded())
            {
                $model = $model->find();
                $this->cached[$column] = $model;
            }
            return $model;
        }

        throw new \Exception("Property {$column} not found on {$this->model_name} model.");
    }
    function __isset($property)
    {
        if (array_key_exists($property, $this->_row))
            return true;
        if (array_key_exists($property, $this->cached))
            return true;
        if (($val = $this->get($property)) !== null)
            return true;
        $relations = array_merge($this->hasOne, $this->hasMany, $this->belongsTo);
        if (isset($relations[$property]))
            return true;

        return false;
    }
    function __set($column, $val)
    {
        $relations = array_merge($this->hasOne, $this->hasMany, $this->belongsTo);
        if (array_key_exists($column, $relations))
            $this->add($column, $val);
        else
            $this->_row[$column] = $val;
        $this->cached = array();
    }

    public function add($relation, $model)
    {
        $rels = array_merge($this->hasOne, $this->hasMany, $this->belongsTo);
        $rel = arr($rels, $relation, false);
        if (!$rel)
            throw new \Exception("Model doesn't have a '{$relation}' relation defined");

        if ($rel['type'] == 'belongsTo') {

            if (!$model->loaded())
                throw new \Exception("Model must be loaded before added to a belongs_to relationship. Probably you haven't saved it.");

            $key = $rel['key'];
            $this->$key = $model->_row[$model->id_field];
            if ($this->loaded())
                $this->save();
        } elseif (isset($rel['through'])) {
            if (!$this->loaded())
                throw new \Exception("Model must be loaded before you try adding 'through' relationships to it. Probably you haven't saved it.");

            if (!$model->loaded())
                throw new \Exception("Model must be loaded before added to a 'through' relationship. Probably you haven't saved it.");

            $exists = $this->conn->query('count')
                ->table($rel['through'])
                ->where(array(array($rel['key'], $this->_row[$this->idField]),array($rel['foreignKey'], $model->_row[$model->idField])))
                ->execute();
            if (!$exists)
                $this->conn->query('insert')
                    ->table($rel['through'])
                    ->data(array($rel['key'] => $this->_row[$this->idField],$rel['foreignKey'] => $model->_row[$model->idField]))
                    ->execute();
        }
        else
        {
            if (!$this->loaded())
                throw new \Exception("Model must be loaded before you try adding 'has_many' relationships to it. Probably you haven't saved it.");

            $key = $rel['key'];
            $model->$key = $this->_row[$this->idField];
            if ($model->loaded())
                $model->save();
        }
        $this->cached = array();
    }
    public function asArray()
    {
        return $this->_row;
    }
    public function countAll()
    {
        $query = clone $this->query;
        $query->type('count');
        return $query->execute();
    }
    public function find()
    {
        $setLimit = $this->limit();
        $res = $this->limit(1)->findAll()->current();
        $this->limit($setLimit);
        return $res;
    }
    public function findAll()
    {
        $paths = $this->prepareRelations();
        return $this->app->orm->result($this->modelName, $this->query->execute(), $paths);
    }
    public function get($property) {}
    public function loaded()
    {
        return $this->_loaded;
    }
    public function modelKey($modelName)
    {
        return str_replace("\\", "_", $modelName)."_id";
    }
    public function prepareRelations()
    {
        $paths = array();
        if (!empty($this->_with)) {
            $fields = array();
            $thisAlias = $this->query->lastAlias();

            foreach ($this->columns() as $column)
                $fields[] = array("{$thisAlias}.{$column}", "{$thisAlias}__$column");

            foreach ($this->_with as $target) {
                $model = $this;
                $modelAlias = $thisAlias;
                $rels = explode(".", $target);
                foreach ($rels as $key => $relName) {
                    $path = implode(".", array_slice($rels, 0, $key + 1));
                    if (isset($paths[$path])) {
                        $model = $paths[$path]['model'];
                        $modelAlias = $paths[$path]['alias'];
                        continue;
                    }

                    $alias = str_replace(".", "_", $path);
                    $modelRels = array_merge($model->hasOne, $model->hasMany, $model->belongsTo);
                    if (isset($modelRels[$relName]))
                        $rel = $modelRels[$relName];
                    else
                        $rel = false;

                    if (!$rel)
                        throw new \Exception("Model {$model->modelName} does not have a $relName relation defined.");

                    if ($rel['type'] == "hasMany")
                        throw new \Exception("Relation of {$model->modelName} is hasMany and cannot be preloaded with with()");

                    $relModel = $this->app->orm->get($rel['model']);

                    if ($rel['type'] == "belongsTo") {
                        $this->query->join(array($relModel->table, $alias), array($modelAlias.'.'.$rel['key'],$alias.'.'.$relModel->id_field), 'left');
                    } else {
                        $this->query->join(array($relModel->table, $alias), array($modelAlias.'.'.$model->idField,$alias.'.'.$rel['key']), 'left');
                    }

                    foreach ($relModel->columns() as $column)
                        $fields[] = array("$alias.$column", "{$alias}__$column");

                    $model = $relModel;
                    $modelAlias = $alias;
                    $paths[$path] = array('alias'=>$modelAlias, 'model' => $model);
                }
            }

            call_user_func_array(array($this->query, 'fields'), $fields);
        }

        return $paths;
    }
    public function query()
    {
        return clone $this->query;
    }
    public function remove($relation, $model = null)
    {
        if (!$this->loaded())
            throw new \Exception("Model must be loaded before you try removing relationships from it.");

        $rels = array_merge($this->hasOne, $this->hasMany, $this->belongsTo);
        $rel = arr($rels, $relation, false);
        if (!$rel)
            throw new \Exception("Model doesn't have a '{$relation}' relation defined");

        if ($rel['type'] != 'belongsTo' && (!$model || !$model->loaded()))
            throw new \Exception("Model must be loaded before being removed from a hasOne or hasMany relationship.");

        if ($rel['type'] == 'belongsTo') {
            $key = $rel['key'];
            $this->$key = null;
            $this->save();
        } elseif (isset($rel['through'])) {
            $this->conn->query('delete')
                ->table($rel['through'])
                ->where(array(
                    array($rel['key'], $this->_row[$this->id_field]),
                    array($rel['foreign_key'], $model->_row[$model->id_field])
                ))
                ->execute();
        }
        else
        {
            $key = $rel['key'];
            $model->$key = null;
            $model->save();
        }
        $this->cached = array();
    }
}