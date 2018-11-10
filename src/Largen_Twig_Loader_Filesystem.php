<?php

namespace Maimake\Largen;


use Twig_Error_Loader;
use Twig_LoaderInterface;
use Twig_Source;

class Largen_Twig_Loader_Filesystem implements Twig_LoaderInterface
{

    protected $paths = [];
    protected $cache = [];
    protected $errorCache = [];


    public function __construct($paths = [])
    {
        $this->paths = $paths;
    }

    public function getSourceContext($name)
    {
        $path = $this->findTemplate($name);

        return new Twig_Source(file_get_contents($path), $name, $path);
    }

    public function getCacheKey($name)
    {
        return $this->findTemplate($name);
    }

    public function exists($name)
    {
        $name = $this->normalizeName($name);

        if (isset($this->cache[$name])) {
            return true;
        }

        return false !== $this->findTemplate($name, false);
    }

    public function findDir($name)
    {
        $name = $this->normalizeName($name);

        if ($this->isAbsolutePath($name))
        {
            if (is_dir($name))
            {
                return $name;
            }
        }else{

            foreach ($this->paths as $path) {
                if (!$this->isAbsolutePath($path)) {
                    $path = base_path($path);
                }

                $filepath = $path.'/'.$name;
                if (is_dir($filepath)) {
                    if (false !== $realpath = realpath($filepath)) {
                        return $realpath;
                    }

                    return $filepath;
                }
            }
        }
    }

    public function isFresh($name, $time)
    {
        return filemtime($this->findTemplate($name)) <= $time;
    }

    /**
     * Checks if the template can be found.
     *
     * @param string $name  The template name
     * @param bool   $throw Whether to throw an exception when an error occurs
     *
     * @return string|false The template name or false
     */
    public function findTemplate($name, $throw = true)
    {
        $name = $this->normalizeName($name);

        if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }

        if (isset($this->errorCache[$name])) {
            if (!$throw) {
                return false;
            }

            throw new Twig_Error_Loader($this->errorCache[$name]);
        }

        if ($this->isAbsolutePath($name))
        {
            if (is_file($name))
            {
                return $this->cache[$name] = $name;
            }
        }else{

            foreach ($this->paths as $path) {
                if (!$this->isAbsolutePath($path)) {
                    $path = base_path($path);
                }

                $filepath = $path.'/'.$name;
                if (is_file($filepath)) {
                    if (false !== $realpath = realpath($filepath)) {
                        return $this->cache[$name] = $realpath;
                    }

                    return $this->cache[$name] = $filepath;
                }
            }
        }


        $this->errorCache[$name] = sprintf('Unable to find template "%s" (looked into: %s).', $name, implode(', ', $this->paths));

        if (!$throw) {
            return false;
        }

        throw new Twig_Error_Loader($this->errorCache[$name]);
    }

    private function normalizeName($name)
    {
        return preg_replace('#/{2,}#', '/', str_replace('\\', '/', $name));
    }

    private function isAbsolutePath($file)
    {
        return strspn($file, '/\\', 0, 1)
            || (strlen($file) > 3 && ctype_alpha($file[0])
                && ':' === $file[1]
                && strspn($file, '/\\', 2, 1)
            )
            || null !== parse_url($file, PHP_URL_SCHEME)
            ;
    }
}