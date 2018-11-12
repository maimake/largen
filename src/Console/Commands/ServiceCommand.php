<?php

namespace Maimake\Largen\Console\Commands;

class ServiceCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'largen:service 
    {name : Service Name}
    {--client= : Client Name}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a service';


    protected function askMoreInfo()
    {
        $this->askIfBlank('client', true, function () {
            return $this->ask("Enter the client name(Optinal)", false);
        }, function ($client) {
            if ($client)
            {
                return $this->findClassByNameOrAsk('client', studly_case("{$client}Client"), "largen:client", ['name' => $client]);
            }
        });
    }

    protected function generateFiles()
    {
        $info = $this->nsPath->getClassInfoByType('service', $this->argument('name'));
        if (blank($this->option('client')))
        {
            $this->template('NoClientService.php', $info['output'], $info);
        }else{
            $info['client_name'] = class_basename($this->option('client'));
            $info['client_classname'] = $this->option('client');
            $this->template('Service.php', $info['output'], $info);
        }
    }
}
