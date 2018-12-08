<?php

namespace Maimake\Largen\Contracts;


interface IRepository
{

    //=====================
    // 增删改
    //=====================

    public function newItem($data);

    public function create($data);

    public function update($where, $data);

    public function delete($where = null);

    public function save($models, $callback = null);

    public function deleteModels($models, $force = false);

    //=====================
    // 查
    //=====================

    public function find($where, $all = false);

    public function findById($id);

    public function paginate($where = null, $perPage = null, $columns = ['*'], $pageName = 'page', $page = null);

    public function exists($where);

    public function count($where = null, $columns = '*');

}
