<?php

namespace Maimake\Largen\Console\Commands;

use Faker\Factory;
use Illuminate\Support\Facades\Schema;
use Maimake\Largen\Migrations\SchemaParser;

class RepositoryCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'largen:repository 
    {name : Model Name}
    {--empty : Empty Repository}';

    protected $table;
    protected $model;
    protected $model_singular;
    protected $model_plural;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a repository';


    protected function askMoreInfo()
    {
        $name = studly_case($this->argument('name'));
        $this->setArgument('name', $name);

        if (!$this->option('empty'))
        {
            $this->table = str_plural(snake_case($name));

            $this->model = $this->findClassByNameOrAsk('model', $name);
            $this->model || $this->abort("Model({$name}) not found !");
            $this->model_singular = str_singular(snake_case(class_basename($this->model)));
            $this->model_plural = str_plural(snake_case(class_basename($this->model)));
        }
    }

    protected function generateFiles()
    {
        if (!$this->option('empty'))
        {
            $info = $this->nsPath->getClassInfoByType('repository', $this->argument('name') . 'Model');
            $info['model_class'] = $this->model;
            $info['model_name'] = class_basename($this->model);
            $info['model_singular'] = $this->model_singular;
            $info['model_plural'] = $this->model_plural;

            $this->template('ModelRepository.php', $info['output'], $info);
        }else{
            $info = $this->nsPath->getClassInfoByType('repository', $this->argument('name'));
            $this->template('Repository.php', $info['output'], $info);
        }

    }
}
