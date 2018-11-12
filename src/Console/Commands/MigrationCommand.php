<?php

namespace Maimake\Largen\Console\Commands;

use Maimake\Largen\Migrations\SchemaParser;
use Maimake\Largen\Migrations\SyntaxBuilder;

class MigrationCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'largen:migration 
    {description? : The description of migration}
    {--table= : Table name}
    {--action= : Create or Add or Remove}
    {--soft-delete : Use soft delete}
    {--schema= : Fields}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a migration';


    protected function askMoreInfo()
    {
        $askAlways = blank($this->argument('description'));

        $this->askIfBlank('description', true, function () {
            return $this->ask("What will you do in this migration");
        }, "snake_case");

        $this->askIfBlank('table', true, function () {
            return $this->ask("Table name");
        }, function ($val) {
            return str_plural(snake_case($val));
        });

        $this->askIfBlank('action', true, function () {
            return $this->choice("Create or Add or Remove", ['create', 'add', 'remove'], 0);
        });

        $this->askIfBlank('schema', false, function () {
            return $this->askTableField();
        });

        if ($this->option('action') != 'create')
        {
            $this->askIfBlank('soft-delete', false, function () {
                return $this->confirm("Use soft delete");
            }, null, $askAlways);
        }
    }

    protected function generateFiles()
    {
        $schema = $this->option('schema');
        $schema = (new SchemaParser())->parse($schema);
        $res = (new SyntaxBuilder())->create($schema, [
            'table' => $this->option('table'),
            'action' => $this->option('action'),
        ]);

        $info = $this->nsPath->getClassInfoByType('migration', $this->argument('description'), '');
        $info['output'] = path_slash(dirname($info['output'])) . date('Y_m_d_His_') . $this->argument('description') . '.php';

        $template = $this->option('action') == 'create' ? 'create.php' : 'alter.php';
        $this->template($template, $info['output'], array_merge($info, $res));
    }
}
