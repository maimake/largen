<?php

namespace Maimake\Largen\Console\Commands;

class MiddlewareCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'largen:middleware 
    {name : Middleware Name}
    {--type= : The type of Middleware.[none|global|group|route]}
    {--key= : The Key of Middleware(Available when the type is group or route)}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a middleware';


    protected function askMoreInfo()
    {
        $type = $this->askIfBlank('type', true, function () {
            return $this->choice("The type of Middleware", ['none','global','group','route'], 0);
        });

        if ($type == 'group' || $type == 'route')
        {
            $this->askIfBlank('key', true, function () {
                return $this->ask('The Key of Middleware');
            }, 'dot_case');
        }
    }

    protected function generateFiles()
    {
        $info = $this->nsPath->getClassInfoByType('middleware', $this->argument('name'));
        $this->template('Middleware.php', $info['output'], $info);

        $fullClassname = $info['fullClassname'];
        $content = "\\{$fullClassname}::class,";
        $type = $this->option('type');
        $key = $this->option('key');

        $path = $this->isApp()
            ? app_path('Http/Kernel.php')
            : $this->nsPath->getRootServiceProviderPath();

        if (file_exists($path))
        {

            if ($type == 'group')
            {
                if(!str_contains(file_get_contents($path), "'$key' => "))
                {
                    $this->insertInArrayToFile($path, "
        '$key' => [

        ],
                    ", 'protected $middlewareGroups = ');
                }
                $this->insertInArrayToFile($path, "\n            {$content}", "'$key' => ");
            }elseif ($type == 'route')
            {
                $this->insertInArrayToFile($path, "\n        '$key' => $content", 'protected $routeMiddleware = ');
            }elseif ($type == 'global')
            {
                $path = app_path('Http/Kernel.php');
                $this->insertInArrayToFile($path, "\n        {$content}", 'protected $middleware = ');
            }

        }else{

            if ($type == 'global')
            {
                $hint = "
    protected \$middleware = [
        $$content,
    ];
                    ";

            }elseif ($type == 'group')
            {
                $hint = "
    protected \$middlewareGroups = [
        '$key' => [
            $content,
        ],
    ];
                    ";
            }elseif ($type == 'route')
            {
                $hint = "
    protected \$routeMiddleware = [
        '$key' => $content,
    ];
                    ";
            }

            $this->comment("\nPlease enable the Middleware yourself.\n$hint");
        }
    }

}
