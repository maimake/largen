<?php

namespace Maimake\Largen\Console\Commands;

class ProviderCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'largen:provider 
    {name? : Provider Name(Available when the type is none)}
    {--type=none : Provider type [none|module|log|event|blade|composer]} 
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a provider';


    protected function askMoreInfo()
    {
        if ($this->option('type') == 'none')
        {
            $this->askIfBlank('name', true, function () {
                return $this->ask('Provider Name');
            });
        }
    }

    protected function generateFiles()
    {
        $type = $this->option('type');
        if ($type == 'none')
        {
            $info = $this->nsPath->getClassInfoByType('provider', $this->argument('name'), 'ServiceProvider');
            $template = 'Provider.php';
        }elseif ($type == 'module')
        {
            $info = $this->nsPath->getClassInfoByType(null, $this->option('module'), 'ServiceProvider');
            $template = 'ModuleServiceProvider.php';
            $this->generateModuleComposer($info['fullClassname']);
        }else
        {
            $info = $this->nsPath->getClassInfoByType('provider', null, null, studly_case($type) . 'ServiceProvider');
            $template = studly_case($type) . 'ServiceProvider.php';
        }

        $this->template($template, $info['output'], $info);

        if ($type != 'module')
        {
            $this->insertProvider($info['fullClassname']);
        }else{
            $this->insertAppProvider($info['fullClassname']);
        }
    }

    private function generateModuleComposer($provider_classname)
    {
        if ($this->isApp())
        {
            $this->abort('Creating a module provider is only supported in module/package mode. Use option --module, please');
        }

        //composer.json
        $this->template('composer.json', $this->nsPath->getRootBasePath());

        $this->changeJsonFile($this->nsPath->getRootBasePath() . 'composer.json', function ($json) use ($provider_classname) {

            $json['name'] = $this->nsPath->getId();
            $json['type'] = 'module';
            $srcPath = $this->nsPath->getRootSrcPath(false, false);
            $json['autoload']['psr-4'][$this->nsPath->getRootNamespace()] = $srcPath;
            $json['autoload']['files'][] = join_path($srcPath, 'helpers.php');

            $seeds = $this->nsPath->getPath('seeder');
            $json['autoload']['classmap'][] = str_after($seeds, $this->nsPath->getRootBasePath());

            $factories = $this->nsPath->getPath('factory');
            $json['autoload']['classmap'][] = str_after($factories, $this->nsPath->getRootBasePath());

            $test_paths = str_after($this->nsPath->getPath('test'), $this->nsPath->getRootBasePath());
            $json['autoload-dev']['psr-4'][$this->nsPath->getNamespace('test')] = $test_paths;

            $json['extra']['laravel']['providers'][] = $provider_classname;

            return $json;
        });
    }
}
