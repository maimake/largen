<?php

namespace Maimake\Largen\Console\Commands;

use Maimake\Largen\Contracts\NsPath\NsPath;
use Symfony\Component\Finder\Finder;

class ModuleCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'largen:module {name : Module Name} {--simple}';

    protected $append_extra_options = false;


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a module';

    protected function createNsPath()
    {
        return app(NsPath::class, [
            'id' => $this->argument('name'),
            'type' => 'module',
        ]);
    }


    protected function askMoreInfo()
    {
        $base_path = $this->nsPath->getRootBasePath();
        mkdir_r($base_path);

        $finder = new Finder();
        $finder->in($base_path);

        if ($finder->count() && !$this->option('force'))
        {
            $this->abort("Directory is not empty: " . $this->nsPath->getRootBasePath());
        }
    }

    protected function generateFiles()
    {
        if (!$this->option('simple'))
        {
            $paths = $this->nsPath->allPaths();
            mkdir_r($paths);
        }

        $module_name = $this->argument('name');

        // composer.json in app
        $this->changeJsonFile(base_path('composer.json'), function ($json) {
            if (empty($json['autoload']['psr-4']) || !in_array('Modules\\', array_keys($json['autoload']['psr-4'])))
            {
                $json['autoload']['psr-4']['Modules\\'] = "Modules/";
            }
            return $json;
        });

        //config
        $this->template('config/config.php', $this->nsPath->getPath('config').$this->nsPath->getAlias().".php", [
            'module_name' => $module_name,
        ]);

        //helper
        $srcPath = $this->nsPath->getRootSrcPath('', false);
        $srcPath = explode(DIRECTORY_SEPARATOR, trim($srcPath, DIRECTORY_SEPARATOR));

        $relativePath = array_reduce($srcPath, function ($item, $res) {
            $res .= "../";
        }, "/");


        $this->template('src/helpers.php', $this->nsPath->getRootSrcPath(), [
            'module_alias' => $this->nsPath->getAlias(),
            'relative_path' => $relativePath,
        ]);

        //route
        $this->template('src/Http/routes.php', $this->nsPath->getRootSrcPath() . 'Http/routes.php', [
            'module_name' => $module_name,
            'module_alias' => $this->nsPath->getAlias(),
            'ns_controller' => $this->nsPath->getNamespace('controller'),
        ]);


        $this->callSameContext("largen:provider", ['--type' => 'module']);
        $this->callSameContext("largen:provider", ['--type' => 'event']);
        $this->callSameContext("largen:provider", ['--type' => 'blade']);
        $this->callSameContext("largen:provider", ['--type' => 'composer']);
        $this->callSameContext("largen:provider", ['name' => 'observer']);

        // TODO:
        //tests

    }

    public function callSameContext($command, array $arguments = [])
    {
        $arguments['--module'] = $this->argument('name');
        return parent::callSameContext($command, $arguments);
    }

}
