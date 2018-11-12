<?php

namespace Maimake\Largen\Console\Commands;

class CmdCommand extends GeneratorCommand
{
    protected $signature = 'largen:command 
    {name : Command Name} 
    {--sign= : Console Signature} 
    {--desc= : Cammand Description}';

    protected $description = 'Create a new Artisan command';

    protected function askMoreInfo()
    {
        $this->askIfBlank('sign', true, function () {
            return $this->ask('The console command\'s signature', "my:".snake_case($this->argument('name')));
        }, 'snake_case');

        $this->askIfBlank('desc', false, function () {
            return $this->ask('The console command\'s description', false);
        });
    }


    protected function generateFiles()
    {
        $info = $this->nsPath->getClassInfoByType('command', $this->argument('name'));
        $this->template('Command.php', $info['output'], $info);
    }
}





