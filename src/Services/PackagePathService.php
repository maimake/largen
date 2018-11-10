<?php
/**
 * Created by PhpStorm.
 * User: mai
 * Date: 2017/12/11
 * Time: 10:13
 */

namespace Maimake\Largen\Services;

class PackagePathService extends NsPathServiceBase
{
    protected $service_type = 'package';

    public function getRootNamespace($ns='')
    {
        $base = $this->id;
        return namespace_case(join_namespace($base, $ns));
    }
}