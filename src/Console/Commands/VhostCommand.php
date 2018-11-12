<?php

namespace Maimake\Largen\Console\Commands;

class VhostCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'largen:vhost 
    {name? : Server Name}
    {--port= : Port Number}
    {--config-dir= : Ngnix vhost config directory}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an nginx vhost config';


    protected function askMoreInfo()
    {
        $this->askIfBlank('name', true, function () {
            return $this->ask("What server name do you prefer", kebab_case($this->projectDirName()) . ".test");
        });

        $port = $this->askIfBlank('port', true, function () {
            return $this->ask("Enter the Port Number", 80);
        }, 'intval');

        assert($port > 0);

        $this->askIfBlank('config-dir', true, function () {
            return $this->ask("Where is your nginx vhost configuration directory", "/usr/local/etc/nginx/servers/");
        });
    }

    protected function generateFiles()
    {
        $server_name = $this->argument('name');

        $this->template("nginx.conf", path_slash($this->option('config-dir')) . "$server_name.conf", [
            'root' => public_path(),
            'server_name' => $server_name
        ]);

        $this->changeEnvFile('APP_URL', "http://$server_name");

        $this->info('Restarting nginx ...');

        $this->systemSudoOrFail("echo '127.0.0.1 $server_name' >> /etc/hosts");
        $this->systemOrFail("sudo nginx -s reload");

        $this->alert("http://$server_name");
    }
}
