<?php

namespace Maimake\Largen\Console\Commands;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;
use Maimake\Largen\Contracts\NsPath\NsPath;
use Maimake\Largen\Services\AppPathService;
use Maimake\Largen\Services\ModulePathService;
use Maimake\Largen\Services\PackagePathService;

abstract class GeneratorCommand extends Command
{
    static protected $overwriteAll;

    protected $append_extra_options = true;

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * @var NsPath
     */
    protected $nsPath;

    protected $contextType;

    protected $template_data = [];

    /**
     * Create a new controller creator command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        if ($this->append_extra_options)
        {
            $this->signature .= ' {--D|subdir=} {--M|module=} {--P|package=}';
        }
        $this->signature .= " {--force}";

        parent::__construct();

        $this->files = $files;
    }

    public function handle()
    {
        $this->contextType = $this->determineContextType();
        $this->nsPath = $this->createNsPath();

        chdir(base_path());
        $this->askMoreInfo();
        $this->generateFiles();
    }


    abstract protected function askMoreInfo();

    abstract protected function generateFiles();

    public function option($key = null)
    {
        if ($key && !$this->hasOption($key)) return null;
        return parent::option($key);
    }

    public function callSameContext($command, array $arguments = [])
    {
        if ($this->isModule())
        {
            $arguments['--module'] = $this->option('module');
        }
        if ($this->isPackage())
        {
            $arguments['--package'] = $this->option('package');
        }
        return $this->call($command, $arguments);
    }


    // =======================================
    // Context
    // =======================================

    private function determineContextType()
    {
        if($this->option('package') && $this->option('module'))
        {
            $this->abort("Conflict with module and package. Don't set both.");
        }

        if ($this->option('package'))
        {
            return 'package';
        }

        if (blank($this->option('module')) || strtolower($this->option('module')) == 'app')
        {
            return 'app';
        }

        return 'module';
    }

    protected function createNsPath()
    {
        if ($this->isModule())
            $id = $this->option('module');
        elseif ($this->isPackage())
            $id = $this->option('package');
        else
            $id = 'app';

        return app(NsPath::class, [
            'id' => $id,
            'type' => $this->contextType,
            'subdir' => $this->option('subdir')
        ]);
    }

    protected function isModule()
    {
        return $this->contextType == 'module';
    }

    protected function isApp()
    {
        return $this->contextType == 'app';
    }

    protected function isPackage()
    {
        return $this->contextType == 'package';
    }

    // =======================================
    // Copy file from templates
    // =======================================

    protected function template($source, $desc, $data=[], $force=false)
    {
        if(preg_match('/(\w+)Command$/', get_called_class(), $matchs))
        {
            $this->copyFile($matchs[1].DIRECTORY_SEPARATOR.$source, $desc, $data, $force);
        }else{
            $this->abort("Largen Comman should be named with suffix 'Command'.");
        }
    }

    protected function templateDir($source, $desc, $data=[], $force=false)
    {
        if(preg_match('/(\w+)Command$/', get_called_class(), $matchs))
        {
            $this->copyDir($matchs[1].DIRECTORY_SEPARATOR.$source, $desc, $data, $force);
        }else{
            $this->abort("Largen Comman should be named with suffix 'Command'.");
        }
    }

    protected function copyFile($source, $desc, $data = [], $force=false)
    {
        $loader = app('largen.twig.loader');
        $path = $loader->findTemplate($source.'.twig', false) ?: $loader->findTemplate($source, false);
        $path || $this->abort("File is not exists: $source");

        $this->printWhenVerbose("From $path");

        if (ends_with($desc, DIRECTORY_SEPARATOR) || is_dir($desc))
        {
            $desc = path_slash($desc) . basename($path);
        }

        if (ends_with($path, '.twig'))
        {
            $desc = str_before($desc, '.twig');
            $content = app('largen.twig')->render($path, array_merge($this->template_data, $data, ['options' => $this->options(), 'arguments' => $this->arguments()]));
        }else{
            $content = file_get_contents($path);
        }

        $this->writeFile($desc, $content, $force);
    }

    protected function copyDir($source, $desc, $data = [], $force=false)
    {
        $loader = app('largen.twig.loader');
        $res = $loader->findDir($source);
        $res || $this->abort("Directory is not found : $source");
        foreach ($this->files->allFiles($res, true) as $file)
        {
            $target = join_path($desc, $file->getRelativePathname());
            $this->copyFile($file->getPathname(), $target, $data, $force);
        }
    }

    protected function writeFile($path, $content, $force=false)
    {
        if ($this->files->exists($path))
        {
            if ($force || $this::$overwriteAll || $this->option('force'))
            {
                $this->info("Overwrite $path");
            }else{
                if ($this->input->isInteractive())
                {
                    $res = 'all';
                }else
                {
                    $res = $this->expand("Overwrite $path ?");
                }

                if ($res == 'all')
                {
                    $this::$overwriteAll = true;
                }
                switch ($res)
                {
                    case 'all':
                    case 'yes':
                        $this->info("Overwrite $path");
                        break;
                    case 'no':
                        $this->info("Skip $path");
                        return;
                    case 'quit':
                        $this->abort("Abort while write file!");
                }
            }
        }else{
            $this->info("Create $path");
        }

        mkdir_r($path);
        file_put_contents($path, $content);
    }


    // =======================================
    // laravel
    // =======================================

    private function _insertProvider($config_path, $classname) {
        $this->insertInArrayToFile($config_path, "\n        $classname::class,", "'providers' =>");
    }

    private function _insertAlias($config_path, $alias, $classname) {
        $this->insertInArrayToFile($config_path, "\n        '$alias' => $classname::class,", "'aliases' =>");
    }

    protected function insertAppProvider($classname)
    {
        $this->_insertProvider(config_path('app.php'), $classname);
    }

    protected function insertAppAlias($alias, $classname)
    {
        $this->_insertAlias(config_path('app.php'), $alias, $classname);
    }

    protected function insertProvider($classname)
    {
        $this->_insertProvider($this->nsPath->getConfigFilePath(), $classname);
    }

    protected function insertAlias($alias, $classname)
    {
        $this->_insertAlias($this->nsPath->getConfigFilePath(), $alias, $classname);
    }




    protected function useClassAtFileHead($path, $classname)
    {
        $this->insertToFile($path, "use $classname;\n", "namespace.*", true);
    }

    protected function changeEnvFile($key, $val, $after_key=null, $before_key=null, $example_val=null)
    {
        $key = strtoupper($key);
        if ($example_val) $example_val = strtoupper($example_val);

        if($this->replaceInFile(base_path('.env'), "$key=$val", "/$key=.*/", true) < 1)
        {
            if ($before_key)
            {
                $this->insertToFile(base_path('.env'), "\n$key=$val", "$before_key=", false, "before");
                if (!is_null($example_val))
                {
                    $this->insertToFile(base_path('.env.example'), "\n$key=$example_val", "$before_key=", false, "before");
                }
            }elseif ($after_key)
            {
                $this->insertToFile(base_path('.env'), "$key=$val\n", "$after_key=", false, "after");
                if (!is_null($example_val))
                {
                    $this->insertToFile(base_path('.env.example'), "$key=$example_val\n", "$after_key=", false, "after");
                }
            }else{
                $this->appendToFile(base_path('.env'), "\n$key=$val");
                if (!is_null($example_val))
                {
                    $this->appendToFile(base_path('.env.example'), "\n$key=$val");
                }
            }
        }
    }

    // =======================================
    // Other
    // =======================================

    protected function projectDirName()
    {
        return basename(base_path());
    }

    protected function isGitClean()
    {
        $res = `git status --porcelain`;
        return empty($res);
    }

    protected function gitCommit($msg)
    {
        if (!file_exists(base_path('.git')))
        {
            $this->systemOrFail("git init .");
        }
        if (!$this->isGitClean())
        {
            $this->systemOrFail("git add -A && git commit -m '$msg'");
        }

    }

    protected function ignoreDir($path)
    {
        mkdir_r($path . '/.gitignore');
        file_put_contents($path . '/.gitignore', "*\n!.gitignore");
    }


    protected function findClassByNameOrAsk($type, $class_name, $create_cmd=null, $arguments=[])
    {
        $classes = $this->findClassesByName($type, $class_name);
        if (count($classes) > 1)
        {
            return $this->choice("Which {$class_name} do you want?", $classes, 0);
        }elseif (count($classes) == 1)
        {
            return $classes[0];
        }elseif (!empty($create_cmd)){
            $res = $this->confirm("$class_name is not found ! Would you like to create one ", true);
            if ($res)
            {
                $this->callSameContext($create_cmd, $arguments);
                return $this->findClassesByName($type, $class_name)[0];
            }
        }
        return null;
    }

    protected function findClassesByName($type, $class_name)
    {
        $classes = $this->getAppAndModulesPathServices()->map(function (NsPath $nsPath) use ($type, $class_name) {
            return $nsPath->findClassesByName($type, $class_name);
        });
        return $classes->collapse()->all();
    }

    protected function findPathsByClassName($type, $class_name)
    {
        $files = $this->getAppAndModulesPathServices()->map(function (NsPath $nsPath) use ($type, $class_name) {
            return $nsPath->findPathsByClassName($type, $class_name);
        });
        return $files->collapse()->all();
    }

    protected function getAppAndModulesPathServices()
    {
        $rtn = [];

        // app
        $rtn[] = new AppPathService();

        //find all modules
        $modules_path = config('largen.map.module.base');
        $modules = array_map(function ($item) {
            $id = basename($item);
            return new ModulePathService($id);
        }, (glob("$modules_path/*/")));
        $rtn = array_merge($rtn, $modules);

        // special modules
        foreach (config('largen.map') as $key => $config)
        {
            if (starts_with($key, "module/"))
            {
                $rtn[] = new ModulePathService(str_after($key, 'module/'));
            }
        }

        $rtn = collect($rtn)->keyBy(function (NsPath $item) {
            return $item->getId();
        });

        return $rtn;
    }



    protected function getModelColumns($model_class, $meaningful_only=true, $table=null)
    {
        if (empty($table))
        {
            $model = new $model_class;
            $table = $model->getTable();
        }
        return $this->getModelColumnsFromTable($table, $meaningful_only);
    }

    protected function getModelColumnsFromTable($table, $meaningful_only=true)
    {
        $columns = Schema::getColumnListing($table);
        if ($meaningful_only)
        {
            return array_filter($columns, function ($column) {
                return (!in_array($column, ["id", "created_at", "updated_at", "deleted_at"])) && !str_contains($column, "_id");
            });
        }
        return $columns;
    }

    protected function isModelBelongsToUser($model_class, $table=null)
    {
        $columns = $this->getModelColumns($model_class, false, $table);
        return in_array("user_id", $columns);
    }
}





