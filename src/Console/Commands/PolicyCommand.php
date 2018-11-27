<?php

namespace Maimake\Largen\Console\Commands;

class PolicyCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'largen:policy {name : Model Name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a policy';

    protected $model;
    protected $model_singular;
    protected $model_plural;


    protected function askMoreInfo()
    {
        $name = studly_case($this->argument('name'));
        $this->setArgument('name', $name);

        $this->model = $this->findClassByNameOrAsk('model', $name);
        $this->model || $this->abort("Model({$name}) not found !");
        $this->model_singular = str_singular(snake_case(class_basename($this->model)));
        $this->model_plural = str_plural(snake_case(class_basename($this->model)));
    }

    protected function generateFiles()
    {
        $info = $this->nsPath->getClassInfoByType('policy', $this->argument('name'));
        $info['model_class'] = $this->model;
        $info['model_name'] = class_basename($this->model);
        $info['model_singular'] = $this->model_singular;
        $info['model_plural'] = $this->model_plural;
        $info['actions'] = ['view', 'update', 'delete', 'restore', 'forceDelete'];

        $this->template('Policy.php', $info['output'], $info);

        $auth_provider_path = app_path('Providers/AuthServiceProvider.php');
        $this->useClassAtFileHead($auth_provider_path, $info['fullClassname']);
        $this->useClassAtFileHead($auth_provider_path, $info['model_class']);
        $this->insertInArrayToFile($auth_provider_path, "\n        {$info['model_name']}::class => {$info['classname']}::class,", 'protected $policies');
    }
}
