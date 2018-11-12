<?php

namespace Maimake\Largen\Console\Commands;

class FacadeCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'largen:facade 
    {name : Facade Name} 
    {--service= : Service name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a facade';


    protected function askMoreInfo()
    {
        $this->askIfBlank('service', true, function () {
            return $this->ask('Enter the Service Name which Facade represents for', $this->argument('name'));
        });
    }

    protected function generateFiles()
    {
        $info = $this->nsPath->getClassInfoByType('facade', $this->argument('name'));
        $this->template('Facade.php', $info['output'], $info);
    }
}
