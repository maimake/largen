<?php
/**
 * Created by PhpStorm.
 * User: mai
 * Date: 2017/12/11
 * Time: 10:13
 */

namespace Maimake\Largen\Services;

class ModulePathService extends NsPathServiceBase
{
    protected $service_type = 'module';

    public function __construct($id, $subdir = null, array $inner_config = [])
    {
        $id = namespace_case($id);
        parent::__construct($id, $subdir, $inner_config);
    }

    public function getRootNamespace($ns='')
    {
        $base = "Modules\\$this->id";
        return namespace_case(join_namespace($base, $ns));
    }
}