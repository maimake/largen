<?php

namespace Maimake\Largen\Console\Commands;


class EnvCommand extends GeneratorCommand
{
    protected $signature = 'largen:env {key?} {value?}';
    protected $description = 'Set ENV variables';

    protected function askMoreInfo()
    {
        if (!$this->argument('key'))
        {
            $list = ['db','mail','redis','pusher'];
            $hint = implode(',', $list);
            $res = $this->anticipate("Which to set [$hint]?", $list);
            if (method_exists($this, "setup_$res"))
            {
                $this->{"setup_$res"}();
                return;
            }else{
                $this->setArgument('key', $res);
            }
        }

        $this->askIfBlank('value', false, function () {
            return $this->ask("Enter the value", false);
        });

        $this->changeEnvFile($this->argument('key'), $this->argument('value'));
    }

    protected function generateFiles()
    {
    }


    private function askEnv($key, $default=false)
    {
        $curr = env($key);
        $val = $this->ask("$key ($curr)", empty($default) ? false : $default);
        $this->changeEnvFile($key, $val);
        return $val;
    }

    private function choiceEnv($key, $choices, $default=null)
    {
        $curr = env($key);
        $val = $this->choice("$key ($curr)", $choices, $default);
        $this->changeEnvFile($key, $val);
        return $val;
    }

    private function confirmEnv($key, $default=false, $true_value='true', $false_value='false')
    {
        $curr = env($key);
        $val = $this->anticipate("$key ($curr)", [$true_value, $false_value], $default ? $true_value : $false_value);
        if ($val != $true_value)
        {
            $val = $false_value;
        }
        $this->changeEnvFile($key, $val);
        return $val;
    }

    private function setup_db()
    {
        $list = ['mysql','sqlite','pgsql','sqlsrv'];
        $db_connection = $this->choiceEnv('DB_CONNECTION', $list, 0);

        if ($db_connection == 'sqlite')
        {
            $db = $this->askEnv('DB_DATABASE', $this->projectDirName() . '.sqlite');

            if (!ends_with($db, '.sqlite'))
            {
                $db .= '.sqlite';
                $this->changeEnvFile('DB_DATABASE', $db);
            }
            touch(storage_path("database/$db"));

            $this->changeEnvFile('DB_HOST', '');
            $this->changeEnvFile('DB_PORT', '');
            $this->changeEnvFile('DB_USERNAME', '');
            $this->changeEnvFile('DB_PASSWORD', '');
        }else{
            $map = [
                'mysql' => '3306',
                'pgsql' => '5432',
                'sqlsrv' => '1433',
            ];
            $this->askEnv('DB_HOST', '127.0.0.1');
            $this->askEnv('DB_PORT', $map[$db_connection]);

            $db = $this->askEnv('DB_DATABASE', $this->projectDirName());
            if (ends_with($db, '.sqlite'))
            {
                $db = str_before($db, '.sqlite');
                $this->changeEnvFile('DB_DATABASE', $db);
            }
            $this->askEnv('DB_USERNAME', 'root');
            $this->askEnv('DB_PASSWORD');
        }
    }


    private function setup_mail()
    {
        $this->askEnv('MAIL_HOST', 'smtp.gmail.com');
        $this->askEnv('MAIL_PORT', '587');
        $this->askEnv('MAIL_USERNAME');
        $this->askEnv('MAIL_PASSWORD');
        $this->askEnv('MAIL_FROM_ADDRESS', env('MAIL_USERNAME'));
        $this->askEnv('MAIL_FROM_NAME', explode('@', env('MAIL_FROM_ADDRESS') ?: env('MAIL_USERNAME'))[0]);
        $this->confirmEnv('MAIL_ENCRYPTION', true, 'tls', '');
    }


    private function setup_redis()
    {
        $this->askEnv('REDIS_HOST', '127.0.0.1');
        $this->askEnv('REDIS_PASSWORD');
        $this->askEnv('REDIS_PORT', '6379');
    }

    private function setup_pusher()
    {
        $this->askEnv('PUSHER_APP_ID');
        $this->askEnv('PUSHER_APP_KEY');
        $this->askEnv('PUSHER_APP_SECRET');
    }

}





