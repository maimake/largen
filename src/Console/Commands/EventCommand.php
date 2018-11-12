<?php

namespace Maimake\Largen\Console\Commands;

class EventCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'largen:event 
    {name : Event Name} 
    {--channel= : Channel name which broadcasts on}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an event';


    protected function askMoreInfo()
    {
        $this->askIfBlank('channel', true, function () {
            return $this->ask('Enter the Channel Name which broadcasts on', 'channel-name');
        }, 'kebab_case');
    }

    protected function generateFiles()
    {
        $info = $this->nsPath->getClassInfoByType('event', $this->argument('name'));
        $this->template('Event.php', $info['output'], $info);
    }
}
