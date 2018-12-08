<?php

namespace Maimake\Largen\Console\Commands;

class SearchRequestCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'largen:search_request {name : Model Name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a Request for searching';


    protected $model;
    protected $model_singular;
    protected $model_plural;

    protected $search_classname;
    protected $search_full_classname;


    protected function askMoreInfo()
    {
        $name = studly_case($this->argument('name'));
        $this->setArgument('name', $name);

        $this->model = $this->findClassByNameOrAsk('model', $name);
        $this->model || $this->abort("Model({$name}) not found !");
        $this->model_singular = str_singular(snake_case(class_basename($this->model)));
        $this->model_plural = str_plural(snake_case(class_basename($this->model)));


        $this->template_data = [
            'model_name' => $name,
            'model_classname' => $this->model,
            'model_singular' => $this->model_singular,
            'model_plural' => $this->model_plural,
            'columns' => $this->getModelColumns($this->model),
            'has_user' => $this->isModelBelongsToUser($this->model),
        ];

    }

    protected function generateFiles()
    {
        $name = $this->argument('name');
        $info = $this->nsPath->getClassInfoByType('request', $name, "SearchRequest");
        $info['search_classname'] = $this->search_classname;
        $info['search_full_classname'] = $this->search_full_classname;
        $this->template('SearchRequest.php', $info['output'], $info);
    }

}
