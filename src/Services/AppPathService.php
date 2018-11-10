<?php
/**
 * Created by PhpStorm.
 * User: mai
 * Date: 2017/12/11
 * Time: 10:13
 */

namespace Maimake\Largen\Services;

class AppPathService extends NsPathServiceBase
{
    protected $service_type = 'app';

    public function __construct($subdir=null, $inner_config=[])
    {
        parent::__construct('app', $subdir, $inner_config);
    }

    public function getViewName($name='')
    {
        return $name;
    }

    public function getRootBasePath($path='')
    {
        $base = path_slash($this->getConfig("base", null));
        return join_path($base, $path);
    }

    public function getRootNamespace($ns='')
    {
        $base = app()->getNamespace();
        return namespace_case(join_namespace($base, $ns));
    }

    public function getRootServiceProviderPath()
    {
        return app_path('Providers/AppServiceProvider.php');
    }
}