<?php

namespace Maimake\Largen\Console\Commands;

use Faker\Factory;
use Illuminate\Support\Facades\Schema;
use Maimake\Largen\Migrations\SchemaParser;

class SeederCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'largen:seeder {name : Model Name}';

    protected $table;
    protected $model;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a seeder';


    protected function askMoreInfo()
    {
        $name = studly_case($this->argument('name'));
        $this->setArgument('name', $name);
        $this->table = str_plural(snake_case($name));

        $this->model = $this->findClassByNameOrAsk('model', $name);
        $this->model || $this->abort("Model({$name}) not found !");
    }

    protected function generateFiles()
    {
        $info = $this->nsPath->getClassInfoByType('seeder', $this->argument('name'));
        $info['model_class'] = $this->model;
        $info['model_name'] = class_basename($this->model);
        $this->template('Seeder.php', $info['output'], $info);

        // insert to App's DatabaseSeeder
        $classname = $info['fullClassname'];
        $this->insertInBlockToFile(database_path('seeds/DatabaseSeeder.php'), "\n        \$this->call($classname::class);", "public function run()");

        $this->dump_autoload();
    }
}
