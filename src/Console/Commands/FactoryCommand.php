<?php

namespace Maimake\Largen\Console\Commands;

use Faker\Factory;
use Illuminate\Support\Facades\Schema;
use Maimake\Largen\Migrations\SchemaParser;

class FactoryCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'largen:factory 
    {name : Model Name}
    {--schema= : Fields}
    ';

    protected $table;
    protected $model;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a factory of model';


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
        $info = $this->nsPath->getClassInfoByType('factory', $this->argument('name'));
        $info['model_class'] = $this->model;
        $info['fields'] = [];

        if (blank($this->option('schema')))
        {
            // from database columns
            $columns = Schema::getColumnListing($this->table);
            foreach ($columns as $column)
            {
                $info['fields'][$column] = $this->createFaker($column, Schema::getColumnType($this->table, $column));
            }

        }else{
            // from schema option
            $schema = $this->option('schema');
            $schema = (new SchemaParser())->parse($schema);
            foreach ($schema as $item)
            {
                $column = $item['name'];
                $info['fields'][$column] = $this->createFaker($column, $item['type'], implode(',', $item['arguments']));
            }
        }
        unset($info['fields']['id']);

        $this->template('ModelFactory.php', $info['output'], $info);
    }

    private function createFaker($name, $type, $arguments=null)
    {
        $name = strtolower($name);

        if ($type == 'enum')
        {
            $val = "{$arguments}[rand(0, count({$arguments})-1)]";
        }else{
            $group = $this->groupIncludeType($type);
            switch ($group)
            {
                case 'foreign':
                    $val = '$faker->numberBetween(1, 50)';
                    break;
                case 'integer':
                    $val = '$faker->randomDigit';
                    break;
                case 'string':
                    $val = '$faker->sentence';
                    break;
                case 'decimal':
                    $val = '$faker->randomFloat';
                    break;
                case 'boolean':
                    $val = '$faker->boolean';
                    break;
                case 'timestamp':
                    $val = '$faker->dateTime';
                    break;
            }

            switch ($type)
            {
                case 'text':
                    $val = '$faker->paragraph';
                    break;
                case 'uuid':
                    $val = '$faker->uuid';
                    break;
                case 'json':
                    $val = '{"abc":123}';
                    break;
                case 'ipAddress':
                    $val = '$faker->ipv4';
                    break;
                case 'macAddress':
                    $val = '$faker->macAddress';
                    break;
            }


            try{
                $prop = camel_case($name);
                Factory::create()->{$prop};
                $val = '$faker->' . $prop;
            }catch (\Exception $e)
            {

            }


            if (ends_with($name, '_id')) $val = '$faker->numberBetween(1, 50)';
            if (str_contains($name, 'name')) $val = '$faker->name';
            if (str_contains($name, 'email')) $val = '$faker->unique()->safeEmail';
            if (str_contains($name, 'phone')) $val = '$faker->unique()->phoneNumber';
            if (str_contains($name, 'password')) $val = '$password ?: $password = bcrypt(\'secret\')';
        }

        return $val ?? "''";
    }

    private function groupIncludeType($type)
    {
        foreach (Command::DB_FIELD_TYPES as $group => $arr)
        {
            foreach ($arr as $item)
            {
                if (strtolower($item) == $type)
                {
                    return $group;
                }
            }
        }
    }
}
