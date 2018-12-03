<?php

namespace Maimake\Largen\Console\Commands;

class RequestCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'largen:request {name : Request Name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a request';


    protected function askMoreInfo()
    {
    }

    protected function generateFiles()
    {
        $name = $this->argument('name');

        $model = $this->findClassByNameOrAsk('model', studly_case($name));
        if ($model)
        {
            $this->template_data = [
                'columns' => $this->getModelColumns($model),
                'has_user' => $this->isModelBelongsToUser($model),
            ];
        }

        $info = $this->nsPath->getClassInfoByType('request', $this->argument('name'));
        $this->template('Request.php', $info['output'], $info);
    }
}
