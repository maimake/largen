<?php

namespace Maimake\Largen\Console\Commands;

class JobCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'largen:job {name : Job Name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a job';


    protected function askMoreInfo()
    {
    }

    protected function generateFiles()
    {
        $info = $this->nsPath->getClassInfoByType('job', $this->argument('name'));
        $this->template('Job.php', $info['output'], $info);
    }
}
