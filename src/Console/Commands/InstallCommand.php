<?php

namespace Maimake\Largen\Console\Commands;

class InstallCommand extends GeneratorCommand
{
    protected $signature = 'largen:install';
    protected $description = 'Install Project to use Largen Tools';

    protected function askMoreInfo()
    {
        $this->alert("This command should be run after creating laravel project right away. \n*     Because it will change some env, config, npm scripts and so on.");
        $this->confirm("Continue", true);
    }

    protected function generateFiles()
    {
        $this->initProject();
        $this->makeAuth();

        $this->copyTemplateFiles();

        $this->changeEnv();
        $this->changeEmail();
        $this->changeLocale();
        $this->changeTimezone();
        $this->changeLogger();
        $this->changeSqliteDbPath();
        $this->activeUserSeeder();
        $this->activeHelpers();
        $this->systemOrFail('./artisan my:replace-str');
        $this->gitCommit('adjust project');

        $this->composerRequire();
        $this->yarnInstall();
        $this->theEnd();
    }

    private function initProject()
    {
//        $gitingore = "
//public/apps/
///public/mix-manifest.json
///public/**/*.map
//
///public/js/*
///public/css/*
///public/fonts/vendor
//
//!/public/**/static
//.phpstorm.meta.php
//";
        $gitingore = "
.phpstorm.meta.php
_ide_helper.php
        ";

        $this->appendToFile(base_path('.gitignore'), $gitingore);
        $this->systemOrFail('chmod +x artisan');
        $this->systemOrFail('chmod -R 777 storage');
        $this->systemOrFail('chmod -R 777 bootstrap/cache');
        $this->systemOrFail('./artisan storage:link');
        $this->gitCommit('init project');
    }

    private function makeAuth() {
        $this->systemOrFail('./artisan make:auth');
        $this->gitCommit('make default auth');
    }


    private function copyTemplateFiles()
    {
        $this->templateDir('', base_path(), null, true);
        $this->systemOrFail('chmod -R +x scripts');

        $this->insertAppProvider('App\\Providers\\BladeServiceProvider');
        $this->insertAppProvider('App\\Providers\\ComposerServiceProvider');
        $this->insertAppProvider('App\\Providers\\ObserverServiceProvider');

        $this->insertToFile(app_path('User.php'), " 'api_token',", "'remember_token',", false, "after", false);
        $this->replaceInFile(resource_path('views/layouts/app.blade.php'), "mix(", "asset(");
    }


    private function changeEnv()
    {
        $this->changeEnvFile("DB_CONNECTION", 'mysql');
        $this->changeEnvFile("DB_HOST", '127.0.0.1');
        $this->changeEnvFile("DB_PORT", '3306');
        $this->changeEnvFile("APP_NAME", $this->projectDirName());
        $this->changeEnvFile("DB_DATABASE", $this->projectDirName());
        $this->changeEnvFile("DB_USERNAME", 'root');
        $this->changeEnvFile("DB_PASSWORD", '');
        $this->changeEnvFile("APP_URL", 'http://localhost:8000');

        $welcome_php = $this->nsPath->getResourcePathByType('view', 'welcome.blade.php');
        $this->replaceInFile($welcome_php, '{{$title}}', 'Laravel');

    }

    private function changeLocale()
    {
        $this->replaceInFile(config_path('app.php'), "'locale' => env('APP_LOCALE', 'en')", "'locale' => 'en'");
        $this->changeEnvFile("APP_LOCALE", "zh", "APP_URL", null, "zh");
    }

    private function changeTimezone()
    {
        $this->replaceInFile(config_path('app.php'), "'timezone' => env('APP_TIMEZONE', 'UTC')", "'timezone' => 'UTC'");
        $this->changeEnvFile("APP_TIMEZONE", "UTC", "APP_URL", null, "UTC");
    }

    private function changeLogger()
    {
        $this->replaceInFile(config_path('logging.php'), "'channels' => ['daily']", "'channels' => ['single']");
    }

    private function changeSqliteDbPath()
    {
        $this->ignoreDir(storage_path('database'));
        $this->replaceInFile(config_path('database.php'), "'database' => resolve_path(env('DB_DATABASE', 'database.sqlite'), '@storage_path/database')", "'database' => env('DB_DATABASE', database_path('database.sqlite'))");
    }

    private function changeEmail()
    {
        $this->insertToFile(base_path('.env'), "MAIL_FROM_ADDRESS=null\n", "MAIL_PORT");
        $this->insertToFile(base_path('.env'), "MAIL_FROM_NAME=null\n", "MAIL_FROM_ADDRESS");

        $this->insertToFile(base_path('.env.example'), "MAIL_FROM_ADDRESS=null\n", "MAIL_PORT");
        $this->insertToFile(base_path('.env.example'), "MAIL_FROM_NAME=null\n", "MAIL_FROM_ADDRESS");

        $this->replaceInFile(config_path('mail.php'), "'address' => env('MAIL_FROM_ADDRESS', env('MAIL_USERNAME'))", "'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com')");
        $this->replaceInFile(config_path('mail.php'), "'name' => env('MAIL_FROM_NAME', env('APP_NAME'))", "'name' => env('MAIL_FROM_NAME', 'Example')");
    }

    private function activeUserSeeder()
    {
        $this->replaceInFile(database_path('seeds/DatabaseSeeder.php'), '$this->call(UsersTableSeeder::class);', '// $this->call(UsersTableSeeder::class);');
    }

    private function activeHelpers()
    {
        $this->changeJsonFile(base_path('composer.json'), function ($json) {
            if (empty($json['autoload']['files']) || !in_array('app/helpers.php', $json['autoload']['files']))
            {
                $json['autoload']['files'][] = 'app/helpers.php';
            }
            if (empty($json['autoload']['psr-4']) || !in_array('Modules\\', array_keys($json['autoload']['psr-4'])))
            {
                $json['autoload']['psr-4']['Modules\\'] = "Modules/";
            }
            return $json;
        });
        $this->dump_autoload();
    }

    private function composerRequire()
    {
        foreach ([
                     'debugbar',
                     'laracasts/flash',
                     'laravelcollective/html',
                     'simplesoftwareio/simple-qrcode',
                     'ide_helper',
                     'moontoast/math',
                     'simplesoftwareio/simple-qrcode',
                     'fedeisas/laravel-mail-css-inliner',
                     'predis/predis',
                 ] as $item)
        {
            $this->call('largen:composer', ['name' => $item]);
            $this->gitCommit("require $item");
        }

        // dev
        foreach (['mnabialek/laravel-sql-logger'] as $item)
        {
            $this->call('largen:composer', ['name' => $item, '--dev' => true]);
            $this->gitCommit("require $item");
        }
    }

    private function yarnInstall()
    {
        $this->changeJsonFile(base_path('package.json'), function ($json) {

//            $json['scripts']['format'] = "prettier --write 'resources/assets/**/*.{js,css,less,scss}' 'public/static/**/*.{js,css,less,scss}'";
            $json['scripts']['hot'] = "cross-env NODE_ENV=development DEV_PORT=`node scripts/get_free_port.js` node_modules/webpack-dev-server/bin/webpack-dev-server.js --inline --hot --config=node_modules/laravel-mix/setup/webpack.config.js";

            $json['scripts']["start"] = "npm run hot";
//            $json['scripts']["dev-all"] = "APP=all npm run dev";
//            $json['scripts']["prod-all"] = "APP=all npm run prod";
//            $json['scripts']["watch-all"] = "node webpack/webpack.mix.all.js";
//            $json['scripts']["hot-web"] = "APP=web npm run watch-all";

            return $json;
        });


        $js_packages = [
//            'prettier',
            'portfinder',
            'ini',
            'browser-sync',
            'browser-sync-webpack-plugin',
//            'babel-preset-env',
//            'babel-preset-stage-2',
//            'babel-plugin-transform-runtime',
            'font-awesome',
//            'floatthead',
//            'select2',
//            'select2-bootstrap-theme',
//            'store',
        ];

        $this->systemOrFail("yarn add -D " . implode(' ', $js_packages));
        $this->gitCommit('install npm packages');
        $this->systemOrFail('yarn run prod');
    }


    private function theEnd()
    {
        $this->info("\nNow, you can setup some environments with following command:\n");

        $projDirName = snake_case($this->projectDirName());
        $this->warn("# Setup DB configurations first please");
        $this->info(">  ./artisan largen:env");
        $this->info("");

        $this->warn("# Will create Admin User(admin@admin.com, password)");
        $this->info(">  ./artisan migrate --seed");
        $this->info("");


        $this->warn("# Run server");
        $this->info(">  ./artisan serve            <== Run server use default url http://localhost:8000");
        $this->warn("Or");
        $this->info(">  ./artisan largen:vhost $projDirName.test     <== Generate a ngnix config file, and add item to /etc/hosts, If you have nginx already local machine");
        $this->warn("Or");
        $this->info(">  ./artisan largen:env APP_URL http://$projDirName.test   <== Only set 'APP_URL',if you have already configured webserver manually");
        $this->info("");

        $this->warn("# Start HMR");
        $this->info(">  yarn start");
        $this->info("");


        $this->warn("# Setup Directories for IDEA IDE (Please open project in IDE first.)");
        $this->info(">  ./artsan largen:idea");
        $this->info("");
    }

}





