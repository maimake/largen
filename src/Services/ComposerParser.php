<?php

namespace Maimake\Largen\Services;


class ComposerParser
{
    private $path;
    private $json;

    public function __construct($path)
    {
        $this->path = $path;
        $content = file_get_contents($path);
        $this->json = \json_decode($content, true);
    }


    public function getBasePath()
    {
        return dirname($this->path);
    }


    public function getType()
    {
        $type = array_get($this->json, 'type', 'library');
        switch ($type)
        {
            case 'project':
                return 'app';
            case 'library':
            case 'package':
                return 'package';
            default: return 'module';
        }
    }

    public function setType($type) {
        switch ($type)
        {
            case 'app':
                $val = 'project';
                break;
            case 'package':
                $val = 'library';
                break;
            default:
                $val = 'module';
                break;
        }
        array_set($this->json, 'type', $val);
    }

    public function getName()
    {
        return array_get($this->json, 'name');
    }

    public function setName($val)
    {
        array_set($this->json, 'name', $val);
    }

    public function getAlias()
    {
        $rtn = preg_split(";[\\/];", $this->getName());
        return snake_case(array_last($rtn));
    }


    public function getRequire($dev=false)
    {
        return array_get($this->json, $dev ? 'require-dev':'require', []);
    }

    public function addRequire($package, $version, $dev=false)
    {
        $key = ($dev ? 'require-dev':'require').$package;
        array_set($this->json, $key, $version);
    }

    public function deleteRequire($package, $dev=false)
    {
        unset($this->json[($dev ? 'require-dev':'require')][$package]);
    }

    public function getRequireCmd($dev=false)
    {
        $concatenatedPackages = '';
        foreach ($this->getRequire($dev) as $name => $version) {
            $concatenatedPackages .= "\"{$name}:{$version}\" ";
        }
        if (!empty($concatenatedPackages))
            return "composer require {$concatenatedPackages}" . ($dev ? '--dev':'');
    }

    public function getAutoloadFiles($dev=false)
    {
        $arr = array_get($this->json, $dev ? 'autoload-dev.files':'autoload.files');
        return array_map(function ($item) {
            return $this->getBasePath() . DIRECTORY_SEPARATOR . $item;
        }, $arr);
    }

    public function save()
    {
        $content = \json_encode($this->json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        file_put_contents($this->path, $content);
    }

    public function mergeRequireFrom(ComposerParser $composerParser)
    {
        foreach ($composerParser->getRequire() as $key => $val)
        {
            $this->addRequire($key, $val);
        }
        foreach ($composerParser->getRequire(true) as $key => $val)
        {
            $this->addRequire($key, $val, true);
        }
    }

}