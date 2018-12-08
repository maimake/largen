<?php

namespace Maimake\Largen\Support\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Maimake\Largen\Contracts\IRepository;
use Maimake\Largen\Contracts\ISearchRequest;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;


abstract class RepositoryBase implements IRepository
{
    protected function buildQuery(&$query, $callback)
    {
        if (is_callable($callback)) {
            $res = call_user_func($callback, $query);
            if (filled($res)) {
                $query = $res;
            }
        } elseif ($callback instanceof ISearchRequest)
        {
            $filters = $callback->getFilters();
            collect($filters)->each(function ($value, $key) use ($query) {
                $this->applyWhere($query, $key, $value);
            });

            $sorters = $callback->getSorters();
            collect($sorters)->each(function ($value, $key) use ($query) {
                $this->applySort($query, $key, $value);
            });
        }else{
            $query->where($callback);
        }
        return $query;
    }


    public function save($models, $callback = null)
    {
        return \DB::transaction(function () use ($models, $callback) {

            $models = array_wrap($models);

            /** @var Model $model */
            foreach ($models as $model)
            {
                if (filled($callback)) {
                    call_user_func($callback, $model);
                }
                $model->saveOrFail();
            }
        });
    }

    public function deleteModels($models, $force = false)
    {
        return \DB::transaction(function () use ($models, $force) {

            $models = array_wrap($models);

            /** @var Model $model */
            foreach ($models as $model)
            {
                $force ? $model->forceDelete() : $model->delete();
            }
        });
    }

    // 解析搜索参数
    protected function applyWhere(Builder $query, $col, $val, $default_compare = '^')
    {
        if (empty($val))
        {
            return;
        }

        $supported_ops = [
            '<=', '>=', '<>',   // compare
            '<', '>', '=',      // compare
            '~', '^', '$',      // like
            ',',                // in (p1,p2,...)
            '-',                // between (p1, p2)
        ];

        assert(in_array($default_compare, $supported_ops), "$default_compare is not a supported operation");


        if (
            starts_with($val, '<=|') ||
            starts_with($val, '>=|') ||
            starts_with($val, '<>|')
        )
        {
            $query->where($col, substr($val, 0, 2), trim(substr($val, 3)));
        }

        elseif (
            starts_with($val, '<|') ||
            starts_with($val, '>|') ||
            starts_with($val, '=|')
        ){
            $query->where($col, substr($val, 0, 1), trim(substr($val, 2)));
        }

        elseif (starts_with($val, '~|'))
        {
            $query->where($col, 'like', "%".trim(substr($val, 2))."%");
        }
        elseif (starts_with($val, '^|'))
        {
            $query->where($col, 'like', trim(substr($val, 2))."%");
        }
        elseif (starts_with($val, '$|'))
        {
            $query->where($col, 'like', "%".trim(substr($val, 2)));
        }

        elseif (starts_with($val, ',|'))
        {
            $params = explode('|', substr($val, 2));
            $query->whereIn($col, array_map('trim', $params));
        }

        elseif (starts_with($val, '-|'))
        {
            $params = explode('|', substr($val, 2));
            $query->whereBetween($col, array_map('trim', $params));
        }

        else{
            $this->applyWhere($query, $col, $default_compare . "|" . $val, $default_compare);
        }
    }

    protected function applySort(Builder $query, $sort, $direction)
    {
        $query->orderBy($sort, $direction);
    }


    public function searchFilter(string $name, $term, $perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        $method = "searchFilters_$name";
        if(method_exists($this, $method))
        {
            $paginator = $this->$method($term, $perPage, $columns, $pageName, $page);
            $paginator->getCollection()->transform(function ($item) {
                return [
                    'id' => $item['name'],
                    'text' => $item['name'],
                ];
            });
            return $paginator;
        }else
        {
            throw new BadRequestHttpException("$name is Not Supported");
        }
    }

}
