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

class Nested
    extends \Lyanna\ORM\Extension
{
    protected function collapseLPosQuery($width, $collapseSelf = false)
    {
        return $this->modify_query(
            $this->model->conn->query('update')
                ->table($this->model->table)
                ->data(array(
                    'rpos' => $this->app->db->expression('rpos - '.($width))
                ))
                ->where('rpos', $collapseSelf?'>=':'>', $this->model->rpos)
        );
    }

    protected function collapseRPosQuery($width)
    {
        return $this->modifyQuery($this->model->conn->query('update')
            ->table($this->model->table)
            ->data(array('rpos' => $this->app->db->expression('lpos - ' . $width)))
            ->where('rpos', '>', $this->model->rpos));
    }

    protected function deleteChildrenQuery()
    {
        return $this->modifyQuery(
            $this->model->conn->query('delete')
                ->table($this->model->table)
                ->where('lpos', '>', $this->model->lpos)
                ->where('rpos', '<', $this->model->rpos)
        );
    }

    protected function maxRPosQuery()
    {
        return $this->modifyQuery($this->model->conn->query('select')->fields($this->app->db->expression('MAX(rpos) as rpos'))
        ->table($this->model->table));
    }
    protected function modifyQuery($query)
    {
        return $query;
    }

    protected function moveTo($parent, $appendToBeggining = false, $childrenOnly = false)
    {
        $width = $this->width();

        if ($childrenOnly)
            $width = $width - 2;
        if ($parent != null && $parent->loaded()) {
            $lpos = $appendToBeggining ? $parent->lpos + 1 : $parent->rpos;
            $depth = $parent->depth + 1;
        } else {
            $lpos = ($appendToBeggining ? 0 : $this->maxRPosQuery()->execute()->current()->rpos) + 1;
            $depth = 0;
        }

        $rpos = $lpos + $width - 1;

        if ($this->model->loaded())
            $this->reverseChildrenPosQuery()->execute();

        $this->padRPosQuery($lpos, $width)->execute();
        $this->padLPosQuery($lpos, $width)->execute();

        if ($this->model->loaded()) {
            $this->collapseLPosQuery($width)->execute();
            $this->collapseRPosQuery($width, $childrenOnly)->execute();
            $depthOffset = $depth - $this->model->depth;

            if ($lpos > $this->model->lpos) {
                $lpos = $lpos - $width;
                $rpos = $rpos - $width;
            }

            $posOffset = $lpos - $this->model->lpos;

            if ($childrenOnly) {
                $posOffset = $posOffset - 1;
                $depthOffset = $depthOffset - 1;
            }

            $this->updateReversedQuery($posOffset, $depthOffset)->execute();
        }

        if (!$childrenOnly) {
            $this->model->lpos = $lpos;
            $this->model->depth = $depth;
            $this->model->rpos = $rpos;
        } else {
            if ($lpos < $this->model->lpos)
                $this->model->lpos = $this->model->lpos + $width;
            $this->model->rpos = $this->model->lpos + 1;
        }
    }

    protected function padLPosQuery($lpos, $width)
    {
        return $this->modifyQuery(
            $this->model->conn->query('update')
                ->table($this->model->table)
                ->data(array(
                    'lpos' => $this->app->db->expression('lpos + '.$width)
                ))
                ->where('lpos', '>=', $lpos)
        );
    }

    protected function padRPosQuery($lpos, $width)
    {
        return $this->modifyQuery(
            $this->model->conn->query('update')
                ->table($this->model->table)
                ->data(array(
                    'rpos' => $this->app->db->expression('rpos + '.$width)
                ))
                ->where('rpos', '>=', $lpos)
        );
    }

    protected function reverseChildrenPosQuery()
    {
        return $this->modifyQuery(
            $this->model->conn->query('update')
                ->table($this->model->table)
                ->data(array(
                    'lpos' => $this->app->db->expression('0-lpos'),
                    'rpos' => $this->app->db->expression('0-rpos')
                ))
                ->where('lpos', '>', $this->model->lpos)
                ->where('rpos', '<', $this->model->rpos));
    }

    protected function updateReversedQuery($posOffset, $depthOffset)
    {
        return $this->modifyQuery(
            $this->model->conn->query('update')
                ->table($this->model->table)
                ->data(array(
                    'lpos' => $this->app->db->expression("0 - lpos + $posOffset"),
                    'rpos' => $this->app->db->expression("0 - rpos + $posOffset"),
                    'depth' => $this->app->db->expression("depth + $depthOffset")
                ))
            ->where('rpos', '<', 0)
        );
    }

    protected function width()
    {
        return $this->model->loaded()?$this->model->rpos - $this->model->lpos + 1 : 2;
    }

    public function children()
    {
        if (!$this->model->loaded())
            throw new \Exception("Model is not loaded.");

        return $this->app->orm->get($this->model->modelName)
            ->where('lpos', '>', $this->model->lpos)
            ->where('rpos', '<', $this->model->rpos)
            ->orderBy('lpos', 'asc');
    }

    public function moveChildren($parent = null, $appendToBeginning = false)
    {
        if (!$this->model->loaded())
            throw new \Exception("Model is not loaded so it has no children");
        if ($this->width() > 2)
            $this->moveTo($parent, $appendToBeginning, true);
        return $this->model;
    }

    public function prepareAppend($parent = null, $appendToBeginning = false)
    {
        $this->moveTo($parent, $appendToBeginning);
        return $this->model;
    }

    public function prepareDelete()
    {
        if (!$this->model->loaded())
            throw new \Exception("Model is loaded, cannot prepare for deletion");

        $width = $this->width();
        if ($width > 2)
            $this->deleteChildrenQuery()->execute();
        $this->collapseRPosQuery($width)->execute();
        $this->collapseLPosQuery($width)->execute();
        return $this->model;
    }
} 