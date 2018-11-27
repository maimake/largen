<?php

namespace Maimake\Largen\Console\Commands;

use Illuminate\Support\Facades\Schema;

class ControllerCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'largen:controller 
    {name : Controller Name or Model Name}
    {--resource : As Resource Controller}
    {--api : As API Controller}
    {--repository : Use repository}
    {--layout= : Layout name}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a controller';


    protected $model;
    protected $model_singular;
    protected $model_plural;
    protected $repo_classname;

    protected function askMoreInfo()
    {
        $as_api = $this->askIfBlank('api', true, function () {
            return $this->confirm('Create as an api controller', false);
        }, null, true);


        $as_resource = $this->askIfBlank('resource', true, function () {
            return $this->confirm('Create as a resource controller', true);
        }, null, true);

        if ($as_resource)
        {
            $this->askIfBlank('repository', true, function () {
                return $this->confirm('Use repository', true);
            }, null, true);
        }

        if (!$as_api)
        {
            $this->askIfBlank('layout', true, function () {
                $path = $this->nsPath->getPath('layout');
                $paths = glob("{$path}*/");
                $paths = array_map(function ($item) {
                    return basename($item);
                }, $paths);

                return $this->choice('Which layout do you prefer', array_merge(['app'], $paths), 0);
            }, function ($val) {
                return $val == 'app' ? '' : $val;
            });
        }
    }

    protected function generateFiles()
    {
        $layout = $this->option('layout') == 'app' ? '' : $this->option('layout');
        $this->template_data['layout'] = "$layout";

        $this->template_data['view_namespace'] = $this->nsPath->getViewName();

        $info = $this->nsPath->getClassInfoByType('controller', $this->argument('name'));

        if ($this->option('resource'))
        {
            $name = $this->argument('name');
            $name = studly_case($name);

            $this->model = $this->findClassByNameOrAsk('model', $name);
            $this->model || $this->abort("Model({$name}) not found !");
            $this->model_singular = str_singular(snake_case(class_basename($this->model)));
            $this->model_plural = str_plural(snake_case(class_basename($this->model)));

            if ($this->option('repository'))
            {
                $this->repo_classname = $this->findClassByNameOrAsk('repository', "{$name}ModelRepository", 'largen:repository', ['name'=>$name]);
            }

            $this->template_data = [
                'model_name' => $name,
                'model_classname' => $this->model,
                'model_singular' => $this->model_singular,
                'model_plural' => $this->model_plural,
                'repo_classname' => $this->repo_classname,
                'columns' => $this->getModelColumns($this->model),
                'has_user' => $this->isModelBelongsToUser($this->model),
            ];

//            $this->callSameContext("largen:request", ['name' => $name]);
//            $this->callSameContext("largen:search_request", ['name' => $name]);
//
//            $request_info = $this->nsPath->getClassInfoByType('request', $name);
//            $search_request_info = $this->nsPath->getClassInfoByType('request', $name, null, "{$name}SearchRequest");

//            $this->template_data['request_class_name'] = $request_info['classname'];
//            $this->template_data['request_full_class_name'] = $request_info['fullClassname'];
//
//            $this->template_data['search_request_class_name'] = $search_request_info['classname'];
//            $this->template_data['search_request_full_class_name'] = $search_request_info['fullClassname'];


            if ($this->option('api'))
            {
                $temp = $this->option('repository') ? 'repoApiResourceController.php' : 'ApiResourceController.php';
                $this->template($temp, $info['output'], $info);
            }else{
                $temp = $this->option('repository') ? 'repoResourceController.php' : 'ResourceController.php';
                $this->template($temp, $info['output'], $info);
                $this->templateDir('views', $this->nsPath->getPath('view', $this->model_plural));

                $base = $this->nsPath->getPath('asset', $layout);
                foreach (['index', 'show', 'create', 'edit'] as $item)
                {
                    $path = join_path($base, 'js/pages', snake_case($this->model_plural), "$item.js");
                    if ($item == 'index')
                    {
                        $this->template('assets/js/index.js', $path);
                    }else{
                        $this->writeFile($path, '');
                    }

                    $path = join_path($base, 'sass/pages', snake_case($this->model_plural), "$item.scss");
                    $this->writeFile($path, '');
                }
            }


        }else{
            $this->template_data['name'] = snake_case($this->argument('name'));
            $this->template('Controller.php', $info['output'], $info);

            if (!$this->option('api'))
            {
                $this->template('index.blade.php', $this->nsPath->getPath('view', $this->template_data['name']));
            }
        }



        // add routes

        $route_file = join_path($this->nsPath->getPath('route'), 'web.php');
        $route_content = file_get_contents($route_file);
        $controller_ns = $info['namespace'];
        $controller_classname = "\\" . $info['fullClassname'];

        if ($this->option('resource'))
        {
            $content = "
Route::get('/$this->model_plural/filter/{name}', '$controller_classname@searchFilter');
Route::resource('/$this->model_plural', '$controller_classname');";
        }else{
            $name = $this->template_data['name'];
            $content = "
Route::get('/$name/index', '$controller_classname@index');";
        }

        if (str_contains($route_content, "'namespace' => '$controller_ns']'"))
        {
            $content = str_replace("\n", "\n\t", $content);
            $this->insertInBlockToFile($route_content, $content, "'namespace' => '$controller_ns']'");
        }else{
            $this->appendToFile($route_file, $content);
        }
    }
}
