<?php

namespace Maimake\Largen\Console\Commands;

class ObserverCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'largen:observer {name : Model Name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a observer';

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
        $info = $this->nsPath->getClassInfoByType('observer', $this->argument('name'));
        $info['model_class'] = $this->model;
        $info['model_name'] = class_basename($this->model);
        $info['model_singular'] = $this->model_singular;
        $info['model_plural'] = $this->model_plural;
        $info['events'] = ['created','creating','updating','updated','saving','saved','deleting','deleted','restoring','restored'];

        $this->template('Observer.php', $info['output'], $info);


        $provider_class = $this->findClassByNameOrAsk('provider', "ObserverServiceProvider", "largen:provider", ["name" => "observer"]);
        $provider_path = $this->findPathsByClassName('provider', $provider_class)[0];

        $this->useClassAtFileHead($provider_path, $info['fullClassname']);
        $this->useClassAtFileHead($provider_path, $info['model_class']);
        $this->insertInBlockToFile($provider_path, "\n        {$info['model_name']}::observe({$info['classname']}::class);", 'public function boot');

        $this->insertAppProvider($provider_class);

    }
}
