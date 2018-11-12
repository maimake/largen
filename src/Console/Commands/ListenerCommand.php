<?php

namespace Maimake\Largen\Console\Commands;

class ListenerCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'largen:listener 
    {name? : Listener Name}
    {--events= : The events to be listened}
    {--type= : The type of listener[Class|Function|Subscriber]}
    {--queue : Handler as a queue job(Available when the type is Class)}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a listener';

    protected $provider_path;

    protected function askMoreInfo()
    {
        $this->askIfBlank('name', true, function () {
            return $this->ask('Listener name');
        }, 'studly_case');



        $this->askIfBlank('events', true, function () {
            return $this->ask("The events to be listened(Separated by comma)");
        }, function ($val) {
            $arr = explode(',', $val);
            $arr = array_filter($arr);
            $items = array_map(function ($item) {
                return $this->ensureEvents(trim($item));
            }, $arr);
            return $items;
        });


        $type = $this->askIfBlank('type', true, function () {
            return $this->choice("The type of Listener", ['Class','Function','Subscriber'], 0);
        });


        if ($type == 'Class')
        {
            $this->askIfBlank('queue', true, function () {
                return $this->confirm('Handler as a queue job');
            }, null, true);
        }

    }

    protected function generateFiles()
    {
        $provider_class = $this->findClassByNameOrAsk('provider', "EventServiceProvider", "largen:provider", ["--type" => 'Event']);
        $this->provider_path = $this->findPathsByClassName('provider', $provider_class)[0];

        switch ($this->option('type'))
        {
            case 'Class':
                $this->generateForClass();
                break;
            case 'Function':
                $this->generateForFunction();
                break;
            case 'Subscriber':
                $this->generateForSubscriber();
                break;
        }
    }


    private function ensureEvents($event)
    {
        return $this->findClassByNameOrAsk('event', studly_case("{$event}Event"), "largen:event", ['name' => $event]);
    }

    private function generateForClass()
    {
        $joined_events = implode('|', array_map(function ($event) {
            return "\\$event";
        }, $this->option('events')));

        $info = $this->nsPath->getClassInfoByType('listener', $this->argument('name'));
        $info['events'] = $joined_events;
        $this->template('Listener.php', $info['output'], $info);

        $fullClassname = $info['fullClassname'];
        foreach ($this->option('events') as $event)
        {
            if(str_contains(file_get_contents($this->provider_path), "'$event' => "))
            {
                $this->insertInArrayToFile($this->provider_path, "\n            '$fullClassname',", "'$event' => ");
            }else{
                $this->insertInArrayToFile($this->provider_path, "
        '$event' => [
            '$fullClassname',
        ],

            ", 'protected $listen =');
            }
        }
    }

    private function generateForFunction()
    {
        $contents = array_map(function ($event) {
            return "            
        Event::listen(\\$event::class, function () {
            // return false; //If want to stop other listeners
        });

            ";
        }, $this->option('events'));

        $this->insertInBlockToFile($this->provider_path, implode('', $contents), 'public function boot');
    }

    private function generateForSubscriber() {

        $info = $this->nsPath->getClassInfoByType('listener', $this->argument('name'), null, $this->argument('name') . "Subscriber");
        $this->template('Subscriber.php', $info['output'], $info);


        if(!str_contains(file_get_contents($this->provider_path), 'protected $subscribe'))
        {
            $this->insertInBlockToFile($this->provider_path, '
            
    protected $subscribe = [

    ];
            ', "class EventServiceProvider", false, 'before');
        }

        $fullClassname = $info['fullClassname'];
        $this->insertInArrayToFile($this->provider_path, "\n        \\$fullClassname::class,", 'protected $subscribe');
    }

}
