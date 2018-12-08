<?php
/**
 * Created by PhpStorm.
 * User: mai
 * Date: 2018/12/6
 * Time: 12:27
 */

namespace Maimake\Largen\Contracts;


interface ISearchRequest
{
    public function getFilters();
    public function getSorters();
    public function toData();
}
