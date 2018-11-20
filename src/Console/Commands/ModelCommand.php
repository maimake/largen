<?php

namespace Maimake\Largen\Console\Commands;

use Illuminate\Support\Facades\Schema;
use Maimake\Largen\Migrations\SchemaParser;
use Maimake\Largen\Migrations\SyntaxBuilder;

class ModelCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'largen:model 
    {name : Model Name}
    {--soft-delete : Use soft delete}
    {--schema= : Fields}
    ';

    protected $table;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a model';


    protected function askMoreInfo()
    {
        $name = studly_case($this->argument('name'));
        $this->setArgument('name', $name);
        $this->table = str_plural(snake_case($name));

        if (!Schema::hasTable($this->table))
        {
            $this->askIfBlank('schema', false, function ()
            {
                return $this->askTableField();
            });
            $this->askIfBlank('soft-delete', false, function () {
                return $this->confirm("Use soft delete");
            }, null, true);


            $schema = $this->option('schema');
            $schema = (new SchemaParser())->parse($schema);
            $columns = array_pluck($schema, 'name');
            $has_user = in_array("user_id", $columns);
            $columns = array_filter($columns, function ($column) {
                return (!in_array($column, ["id", "created_at", "updated_at", "deleted_at"])) && !str_contains($column, "_id");
            });


        }else{
            if (Schema::hasColumn($this->table, 'deleted_at'))
            {
                $this->setOption('soft-delete', true);
            }

            $columns = $this->getModelColumnsFromTable($this->table);
            $has_user = $this->isModelBelongsToUser(null, $this->table);
        }

        $this->template_data = [
            'first_column' => array_first($columns),
            'columns' => $columns,
            'has_user' => $has_user,
        ];

    }

    protected function generateFiles()
    {
        $name = $this->argument('name');

        $info = $this->nsPath->getClassInfoByType('model', $name, null, $name);
        $info['table'] = $this->option('table');

        $this->template('Model.php', $info['output'], $info);

        $this->createMigration();
        $this->createFactory();
        $this->createSeeder();
//        $this->createRepository();

        if (!Schema::hasTable($this->table))
        {
            $seederClass = array_first($this->findClassesByName('seeder', "{$name}Seeder"));

            if($this->confirm("Do you want to do 'migrate' and 'seed' right now? But please make sure you have checked the generated 'migration' and 'seeder' file.", true))
            {
                $this->dump_autoload();
                $this->call("migrate");
                $this->call("db:seed", [
                    "--class" => $seederClass,
                ]);
            }else{
                $this->info("Please do it yourself after checked the generated 'migration' and 'seeder' file.");
                $this->warn("
    > ./artisan migrate
    > ./artisan db:seed --class=$seederClass
                ");
            }
        }
    }

    private function createMigration()
    {
        if (!Schema::hasTable($this->table))
        {
            $this->callSameContext('largen:migration', [
                "description" => "create_{$this->table}_table",
                "--table" => $this->table,
                "--schema" => $this->option('schema'),
                "--action" => 'create',
                "--soft-delete" => $this->option('soft-delete'),
            ]);
        }
    }

    private function createFactory()
    {
        $name = $this->argument('name');
        if (empty($this->findClassesByName('factory', "{$name}Factory")))
        {
            $this->callSameContext('largen:factory', [
                "name" => $name,
                "--schema" => $this->option('schema'),
            ]);
        }
    }

    private function createSeeder()
    {
        $name = $this->argument('name');
        if (empty($this->findClassesByName('seeder', "{$name}Seeder")))
        {
            $this->callSameContext('largen:seeder', [
                "name" => $name,
            ]);
        }
    }


    private function createRepository()
    {
        $name = $this->argument('name');
        if (empty($this->findClassesByName('repository', "{$name}Repository")))
        {
            $this->callSameContext('largen:repository', [
                "name" => $name,
            ]);
        }
    }
}
