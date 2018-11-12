<?php

namespace Maimake\Largen\Console\Commands;

class FunCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'largen:fun {name : Function Name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a helper function';


    protected function askMoreInfo()
    {

    }

    protected function generateFiles()
    {
        $name = snake_case($this->argument('name'));

        $output = $this->nsPath->getRootSrcPath('helpers.php');

        $this->appendToFile($output, "
if (! function_exists('$name'))
{
    function $name() {
        return '$name is called';
    }
}
        ");

    }
}
