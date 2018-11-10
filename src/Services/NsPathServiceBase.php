<?php
/**
 * Created by PhpStorm.
 * User: mai
 * Date: 2017/12/11
 * Time: 10:29
 */

namespace Maimake\Largen\Services;


use Maimake\Largen\Contracts\NsPath\NsPath;

abstract class NsPathServiceBase implements NsPath
{
    protected $id;
    protected $subdir;
    protected $service_type;
    protected $inner_config;

    public function __construct($id, $subdir=null, $inner_config=[])
    {
        $this->id = $id;
        $this->subdir = $subdir;
        $this->inner_config = $inner_config;
    }

    // =======================================
    // base info
    // =======================================

    public function getId()
    {
        return $this->id;
    }

    public function getAlias()
    {
        $rtn = preg_split(";[\\/];", $this->id);
        return snake_case(array_last($rtn));
    }

    public function getViewName($name='')
    {
        return $this->getAlias() . "::" . $name;
    }

    abstract public function getRootNamespace($ns='');

    protected function getConfig($key, $default=null, &$is_accurate=null)
    {
        $is_accurate = false;

        if (!is_null(array_get($this->inner_config, $key))) {
            $is_accurate = true;
            return array_get($this->inner_config, $key);
        }

        $prefix = snake_case($this->id) . ".largen.";

        if (!is_null(config($prefix . $key))) {
            $is_accurate = true;
            return config($prefix . $key);
        }

        $prefix = "largen.map.$this->service_type/$this->id.";

        if (!is_null(config($prefix . $key))) {
            $is_accurate = true;
            return config($prefix . $key);
        }

        $prefix = "largen.map.$this->service_type.";

        if (!is_null(config($prefix . $key))) {
            return config($prefix . $key);
        }

        if ($this->service_type == 'module' || $this->service_type == 'package')
        {
            $prefix = "largen.map.app.";

            if (!is_null(config($prefix . $key))) {
                return config($prefix . $key);
            }
        }

        return $default;
    }

    public function getRootBasePath($path='')
    {
        $base = path_slash($this->getConfig("base", null, $is_accurate));
        return join_path($base, ($is_accurate ? '' : path_slash($this->id)) , $path);
    }

    public function getRootSrcPath($path='', $withBase = true)
    {
        $src = path_slash($this->getConfig("paths.src"));

        if (!$withBase)
            return join_path($src, $path);

        $base = $this->getRootBasePath();
        return join_path($base, $src, $path);
    }

    public function getRootServiceProviderPath()
    {
        $classname = namespace_case($this->getAlias()) . "ServiceProvider";
        return $this->getRootSrcPath("$classname.php");
    }

    // =======================================
    // type info
    // =======================================



    public function getNamespace($type, $subdir=null)
    {
        if (empty($type))
            return $this->getRootNamespace($subdir);
        
        $ns = $this->getConfig("namespace.${type}", '');
        if (blank($ns))
            return '';

        $ns = $this->getRootNamespace(join_namespace($ns, $subdir));
        return namespace_case($ns);
    }

    public function getPath($type, $subdir=null)
    {
        if (empty($type))
        {
            $path = $this->getRootSrcPath($subdir);
            return path_slash($path);
        }

        $path = $this->getConfig("paths.$type");

        if (blank($path))
        {
            //use namespace
            $ns = $this->getNamespace($type);
            $ns = str_after($ns, $this->getRootNamespace('/'));
            $path = $this->getRootSrcPath(namespace_case(join_namespace($ns, $subdir)));
        }else{
            $path = $this->getRootBasePath(join_path($path, $subdir));
        }

        return path_slash($path);
    }



    public function getClassInfoByType($type, $name, $classname_suffix = null, $classname = null, $subdir='', $file_ext = '.php')
    {
        $namespace = $this->getNamespace($type, join_path($this->subdir, $subdir));
        $path = $this->getPath($type, join_path($this->subdir, $subdir));

        if (!isset($classname))
        {
            if (!isset($classname_suffix))
            {
                $classname_suffix = studly_case($type);
            }
            $classname = studly_case($name) . $classname_suffix;
        }

        $fullClassname = join_namespace($namespace, $classname);
        $output = $path . $classname . $file_ext;

        return compact('namespace', 'classname', 'fullClassname', 'output');
    }

    public function getResourcePathByType($type, $path='')
    {
        $base = $this->getPath($type, $this->subdir);
        return join_path($base, $path);
    }

    public function findClassesByName($type, $class_name)
    {
        $files = $this->findPathsByClassName($type, $class_name);

        return array_map(function ($file) use ($class_name) {
            $content = file_get_contents($file);
            if(preg_match("/namespace\s+([^\s;]*)/", $content, $matchs))
            {
                return $matchs[1] . "\\" . $class_name;
            }else{
                return $class_name;
            }

        }, $files);
    }

    public function findPathsByClassName($type, $class_name)
    {
        $class = class_basename($class_name);
        $ns = trim(str_before($class_name, $class), '\\');

        if (empty($type))
        {
            $path = $this->getRootSrcPath();
        }else{
            $info = $this->getClassInfoByType($type, null, null, $class_name);
            $path = dirname($info['output']);
        }

        if (is_dir($path))
        {
            $files = `grep "class $class\b" "$path" -R -l`;
        }

        $files = empty($files) ? [] : explode(PHP_EOL, trim($files));

        if ($class_name == "User" && $type == "model")
        {
            // append App\User
            if (file_exists(app_path('User.php')))
            {
                $files[] = app_path('User.php');
            }
        }

        if (!empty($ns))
        {
            foreach ($files as $file)
            {
                $content = file_get_contents($file);
                if(preg_match("/namespace\s+([^\s;]*)/", $content, $matchs) && $matchs[1] == $ns)
                {
                    return [$file];
                }
            }

            return [];
        }

        return $files;
    }

    public function getConfigFilePath()
    {
        $base = $this->getPath('config');
        return join_path($base, $this->getAlias() . '.php');
    }

    public function allPaths()
    {
        $paths = array_keys(array_merge(
            config("largen.map.app.paths") ?? [],
            config("largen.map.$this->service_type.paths") ?? [],
            config("largen.map.$this->service_type/$this->id.paths") ?? [],
            config("$this->id.largen.map.paths") ?? [],
            array_get($this->inner_config, 'paths') ?? []
        ));

        $namespaces = array_keys(array_merge(
            config("largen.map.app.namespace") ?? [],
            config("largen.map.$this->service_type.namespace") ?? [],
            config("largen.map.$this->service_type/$this->id.namespace") ?? [],
            config("$this->id.largen.map.namespace") ?? [],
            array_get($this->inner_config, 'namespace') ?? []
        ));

        $paths = collect(array_merge($namespaces, $paths));

        return $paths->map(function ($item) {
            return $this->getPath($item);
        })->sort('strcasecmp')->unique()->values()->all();
    }

}