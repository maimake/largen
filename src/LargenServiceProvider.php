<?php

namespace Maimake\Largen;


use Illuminate\Database\Eloquent\Builder;
use Maimake\Largen\Contracts\NsPath\NsPath;
use Maimake\Largen\Services\AppPathService;
use Maimake\Largen\Services\ModulePathService;
use Maimake\Largen\Services\PackagePathService;
use Maimake\Largen\Support\Providers\ModuleServiceProvider as ServiceProvider;
use Twig_Environment;
use Twig_Lexer;

class LargenServiceProvider extends ServiceProvider
{
    protected $composer_path = __DIR__ . "/../composer.json";

    public function register()
    {
        parent::register();

        $this->app->bind(NsPath::class, function ($app, $arguments) {

            $id = $arguments['id'];
            $type = $arguments['type'];
            $subdir = $arguments['subdir'] ?? null;
            $config = $arguments['config'] ?? [];

            switch ($type)
            {
                case 'app': return new AppPathService($subdir, $config);
                case 'module': return new ModulePathService($id, $subdir, $config);
                case 'package': return new PackagePathService($id, $subdir, $config);
            }

            return null;
        });

        // twig
        $this->app->singleton('largen.twig.loader', function ($app) {
            $paths = config('largen.stubs', []);
            $paths[] = largen_path('stubs');
            return new Largen_Twig_Loader_Filesystem($paths);
        });

        $this->app->singleton('largen.twig', function ($app) {
            $twig = new Twig_Environment($app->make('largen.twig.loader'), [
                'autoescape' => false,
            ]);
            $lexer = new Twig_Lexer($twig, array(
                'tag_comment'  => array('<%#', '%>'),
                'tag_block'    => array('<%', '%>'),
                'tag_variable' => array('<%=', '%>'),
            ));
            $twig->setLexer($lexer);
            return $twig;
        });
    }



    public function boot()
    {
        parent::boot();
        // stubs
        $this->publishes([
            largen_path('stubs') => base_path('stubs'),
        ], 'stub');

//        Builder::macro('build', function (...$args) {
//            return (new \Maimake\Largen\Support\Database\Eloquent\Builder($this))->build(...$args);
//        });
    }

    protected function loadCommands() {
        if (config('largen.active_commands.enable')
            && !\App::environment(config('largen.active_commands.blacklist')))
        {
            parent::loadCommands();
        }
    }

}



