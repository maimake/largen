<?php

namespace Maimake\Largen\Support\Repositories;

use Illuminate\Database\Eloquent\Model;


abstract class RepositoryBase
{
    protected function buildQuery(&$query, $callback)
    {
        if (filled($callback)) {
            $res = call_user_func($callback, $query);
            if (filled($res)) {
                $query = $res;
            }
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

}
