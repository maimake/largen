<?php

namespace Maimake\Largen\Console\Commands;

class ExceptionCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'largen:exception {name : Exception Name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an exception';


    protected function askMoreInfo()
    {
    }

    protected function generateFiles()
    {
        $info = $this->nsPath->getClassInfoByType('exception', $this->argument('name'));
        $this->template('Exception.php', $info['output'], $info);
    }
}
