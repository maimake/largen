<?php

namespace Maimake\Largen\Console\Commands;

class ClientCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'largen:client {name : Client name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a client';


    protected function askMoreInfo()
    {
    }

    protected function generateFiles()
    {
        $info = $this->nsPath->getClassInfoByType('client', $this->argument('name'));
        $this->template('HttpClient.php', $info['output'], $info);
    }
}
