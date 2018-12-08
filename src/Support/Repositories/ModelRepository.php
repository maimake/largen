<?php

namespace Maimake\Largen\Support\Repositories;

use Illuminate\Database\Eloquent\Builder;

class ModelRepository extends RepositoryBase
{
    protected $modelClass;

    public function newQuery($where = null): Builder
    {
        $query = $this->modelClass::query();

        if (filled($where))
        {
            $query = $this->buildQuery($query, $where);
        }

        return $query;
    }


    public function count($where = null, $columns = '*')
    {
        return $this->newQuery($where)->count($columns);
    }

    public function exists($where)
    {
        return $this->newQuery($where)->exists();
    }

    public function newItem($data)
    {
        return new $this->modelClass($data);
    }

    public function create($data)
    {
        return \DB::transaction(function () use ($data) {
            return $this->modelClass::create($data);
        });
    }

    public function update($where, $data)
    {
        return \DB::transaction(function () use ($where, $data) {
            $rows = $this->newQuery($where)->update($data);
            return $rows;
        });
    }


    public function delete($where = null)
    {
        return \DB::transaction(function () use ($where) {
            $rows = $this->newQuery($where)->delete();
            return $rows;
        });
    }

    public function paginate($where = null, $perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        $query = $this->newQuery($where);
        return $query->paginate($perPage, $columns, $pageName, $page);
//        return $query->simplePaginate($perPage, $columns, $pageName, $page);
    }

    public function find($where, $all = false)
    {
        $query = $this->newQuery($where);
        return $all ? $query->get() : $query->first();
    }

    public function findById($id)
    {
        return $this->modelClass::find($id);
    }
}
