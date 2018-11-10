<?php

namespace Maimake\Largen\Support\Providers;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use Maimake\Largen\Contracts\NsPath\NsPath;
use Maimake\Largen\Services\ComposerParser;
use ReflectionClass;
use Symfony\Component\Finder\Finder;
use Illuminate\Console\Application as Artisan;

abstract class ModuleServiceProvider extends ServiceProvider
{
    protected $composer_path;

    protected $namespace;

    protected $config_path;
    protected $langs_path;

    protected $factories_path;
    protected $seeds_path;
    protected $migrations_path;

    protected $views_path;
    protected $assets_path;
    protected $public_path;
    protected $routes_path;
    protected $commands_path;
    protected $webpack_path;

    protected $middlewareGroups = [];
    protected $routeMiddleware = [];

    /**
     * @var ComposerParser
     */
    protected $composerParser;

    /**
     * @var NsPath
     */
    protected $nsPath;

    public function __construct(\Illuminate\Contracts\Foundation\Application $app)
    {
        parent::__construct($app);

        $this->composerParser = new ComposerParser($this->composer_path);
        foreach ($this->composerParser->getAutoloadFiles() as $path)
        {
            require_once $path;
        }

        $this->namespace = $this->composerParser->getAlias();
        $this->config_path = join_path($this->composerParser->getBasePath(), "config/$this->namespace.php");
    }

    public function register()
    {
        $this->registerProviders();
        $this->mergeConfigFrom($this->config_path, $this->namespace);
    }

    protected function registerProviders()
    {
        $providers = config("$this->namespace.providers", []);
        foreach ($providers as $provider)
        {
            $this->app->register($provider);
        }
    }

    public function boot()
    {
        $this->loadAliases();

        $this->nsPath = $this->app->make(NsPath::class, [
            'id' => $this->composerParser->getName(),
            'type' => $this->composerParser->getType(),
            'config' => [
                'base' => $this->composerParser->getBasePath(),
            ],
        ]);

        $this->setPaths();

        $this->loadConfig();
        $this->loadMiddleware();
        $this->loadTranslations();

        $this->loadFactories();
        $this->loadSeeds();
        $this->loadMigrations();

        $this->loadViews();
        $this->loadAssets();
        $this->loadPublic();
        $this->loadRoutes();
        $this->loadCommands();

    }
    
    protected function loadAliases()
    {
        $aliases = config("$this->namespace.providers", []);
        foreach ($aliases as $alias => $class)
        {
            AliasLoader::getInstance()->alias($alias, $class);
        }
    }

    protected function setPaths()
    {
        $this->langs_path = $this->nsPath->getPath('lang');

        $this->factories_path = $this->nsPath->getPath('factory');
        $this->seeds_path = $this->nsPath->getPath('seeder');
        $this->migrations_path = $this->nsPath->getPath('migration');

        $this->views_path = $this->nsPath->getPath('view');
        $this->assets_path = $this->nsPath->getPath('asset');
        $this->public_path = $this->nsPath->getPath('public');
        $this->webpack_path = $this->nsPath->getPath('webpack');

        $this->commands_path = $this->nsPath->getPath('command');
        $this->routes_path = $this->nsPath->getPath('route');
    }

    protected function loadConfig()
    {
        $path = $this->config_path;
        $this->publishes([
            $path => config_path(basename($path)),
        ], 'config');
    }

    protected function loadRoutes()
    {
        $path = $this->routes_path;
        foreach (glob("{$path}*.php") as $file)
        {
            $this->loadRoutesFrom($file);
        }
    }

    protected function loadMigrations()
    {
        $path = $this->migrations_path;
        $this->loadMigrationsFrom($path);

        $this->publishes([
            $path => database_path('migrations'),
        ], 'migrations');
    }

    protected function loadTranslations()
    {
        $path = $this->langs_path;
        $this->loadTranslationsFrom($path, $this->namespace);

        $this->publishes([
            $path => resource_path("lang/vendor/" . $this->namespace),
        ], 'lang');
    }

    protected function loadViews()
    {
        $path = $this->views_path;
        $this->loadViewsFrom($path, $this->namespace);

        $this->publishes([
            $path => resource_path("views/vendor/" . $this->namespace),
        ], 'views');
    }

    protected function loadCommands()
    {
        if ($this->app->runningInConsole() || env('APP_DEBUG', false))
        {
            $path = $this->commands_path;
            $this->loadCommandsFrom($path);
        }
    }

    protected function loadAssets()
    {
//        $path = $this->assets_path;
//        $this->publishes([
//            $path => resource_path("assets/vendor/" . $this->namespace),
//        ], 'assets');
    }

    protected function loadPublic()
    {
        $path = $this->public_path;
        $this->publishes([
            $path => public_path("vendor/" . $this->namespace),
        ], 'public');
    }

    protected function loadMiddleware()
    {
        $router = $this->app->make('router');

        foreach ($this->middlewareGroups as $key => $middleware) {
            $router->middlewareGroup($key, $middleware);
        }

        foreach ($this->routeMiddleware as $key => $middleware) {
            $router->aliasMiddleware($key, $middleware);
        }
    }

    protected function loadFactories()
    {
        $path = $this->factories_path;
        $this->app->make(Factory::class)->load($path);
    }

    protected function loadSeeds()
    {
        $path = $this->seeds_path;

        if (is_dir($path)) {
            foreach (Finder::create()->files()->name('*.php')->in($path) as $file) {
                require_once $file->getRealPath();
            }
        }
    }


    // =======================================
    // helper
    // =======================================


    protected function loadCommandsFrom($paths)
    {
        $paths = array_unique(is_array($paths) ? $paths : (array) $paths);

        $paths = array_filter($paths, function ($path) {
            return is_dir($path);
        });

        if (empty($paths)) {
            return;
        }

        foreach ((new Finder)->in($paths)->files()->name('*.php') as $command) {

            $content = $command->getContents();
            if (preg_match("/namespace\s+([^\s;]*)/", $content, $matchs))
            {
                $ns = $matchs[1];
                $classname = $command->getBasename('.php');
                $command = $ns . '\\' . $classname;

                if (is_subclass_of($command, Command::class) &&
                    ! (new ReflectionClass($command))->isAbstract()) {
                    Artisan::starting(function ($artisan) use ($command) {
                        $artisan->resolve($command);
                    });
                }
            }
        }
    }

}



