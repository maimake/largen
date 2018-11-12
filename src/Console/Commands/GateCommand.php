<?php

namespace Maimake\Largen\Console\Commands;

class GateCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'largen:gate 
    {name : Action name or Model name}
    {--policy= : Policy name}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a gate';


    protected function askMoreInfo()
    {
        $name = $this->argument('name');
        $name = kebab_case($name);
        $this->setArgument('name', $name);

        $this->askIfBlank('policy', false, function ()
        {
            return $this->ask("Enter the policy name(Optinal)", false);
        }, function ($policy) {
            if ($policy)
            {
                return $this->findClassByNameOrAsk('policy', studly_case("{$policy}Policy"), "largen:policy", ['name' => $policy]);
            }
        });
    }

    protected function generateFiles()
    {
        $name = $this->argument('name');
        if ($this->option('policy'))
        {
            $policy = $this->option('policy');
            $content = "
        Gate::resource('$name', '$policy', [
          'view' => 'view'
        ]);
            ";
        }else{
            $content = "
        Gate::define('$name', function (\$user, \$model) {
            return \$user->id == \$model->user_id;
        });
            ";
        }

        $auth_provider_path = app_path('Providers/AuthServiceProvider.php');

        $this->insertInBlockToFile($auth_provider_path, $content, "public function boot");
    }
}
