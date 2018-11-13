<?php

namespace Maimake\Largen\Console\Commands;

use Illuminate\Support\Facades\DB;
use Maimake\Largen\Services\ComposerParser;

class ComposerCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'largen:composer 
    {name? : Package name}
    {--dev : Add requirement to require-dev} 
    {--provider= : Add provider to app config}
    {--alias= : Add alias to app config}
    {--alias-class= : The alias Class name}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a dependent package';

    protected function askMoreInfo()
    {
        if (!$this->argument('name'))
        {
            $built_in = ['ide_helper', 'debugbar', 'passport', 'socialite'];
            $res = $this->anticipate('Enter package name ['.implode(',', $built_in).']' , $built_in);
            $this->setArgument('name', $res);

            if (!method_exists($this, $res))
            {
                $this->askIfBlank('dev', true, function () {
                    return $this->confirm('Add to require-dev', $this->option('dev'));
                }, null, true);

                $this->askIfBlank('provider', false, function () {
                    return $this->ask('Enter the provider class', false);
                }, 'namespace_case');

                $this->askIfBlank('alias', false, function () {
                    return $this->ask('Enter the alias', false);
                });

                if ($this->option('alias'))
                {
                    $this->askIfBlank('alias-class', true, function () {
                        return $this->ask('Enter the alias class');
                    }, 'namespace_case');
                }
            }
        }
    }

    protected function generateFiles()
    {
        $name = $this->argument('name');
        if (method_exists($this, $name))
        {
            $this->{$name}();
        }else{
            $this->composer($name, $this->option('dev'), $this->option('provider'), $this->option('alias'), $this->option('alias_class'));
        }
    }

    private function composer($package, $dev, $provider_class=null, $alias=null, $alias_class=null)
    {
        $this->systemOrFail("composer require $package" . ($dev ? " --dev " : ""));
        if (filled($provider_class))
        {
            $this->insertAppProvider($provider_class);
        }
        if (filled($alias))
        {
            $this->insertAppAlias($alias, $alias_class);
        }



        if (!$this->isApp())
        {
            $composerParser = new ComposerParser(base_path('composer.json'));
            $requires = $composerParser->getRequire($dev);

            $version = $requires[$package];
            if (filled($version))
            {
                $composer_file = $this->nsPath->getRootBasePath('composer.json');
                $this->changeJsonFile($composer_file, function ($json) use ($package, $version, $dev) {
                    $json[$dev ? 'require-dev':'require'][$package] = $version;
                    return $json;
                });
            }
        }
    }

    private function ide_helper()
    {
        $this->composer('barryvdh/laravel-ide-helper', false);
        $this->composer('doctrine/dbal', false);
        $this->changeJsonFile(base_path('composer.json'), function ($json) {
            $json['scripts']['post-autoload-dump'][] = '@php artisan ide-helper:generate';
            $json['scripts']['post-autoload-dump'][] = '@php artisan ide-helper:meta';
            return $json;
        });
        $this->systemOrFail('./artisan ide-helper:generate');
        $this->systemOrFail('./artisan ide-helper:meta');
    }

    private function debugbar()
    {
        $this->composer('barryvdh/laravel-debugbar', true);
        $this->ignoreDir(storage_path('debugbar'));
    }

    private function passport()
    {
        // composer
        $this->composer('laravel/passport', false);
        $config_file = join_path($this->nsPath->getPath('config'), 'auth.php');
        $this->replaceInFile($config_file, "'driver' => 'passport',", "'driver' => 'token',");

        $this->systemOrFail('./artisan migrate');
        $this->systemOrFail('./artisan passport:install');
        $this->systemOrFail('./artisan vendor:publish --tag=passport-components');

        // User php
        $user_php = array_first($this->findPathsByClassName('model', 'User'));
        $this->useClassAtFileHead($user_php, "Laravel\\Passport\\HasApiTokens");
        $this->insertInBlockToFile($user_php, "    use HasApiTokens;\n", "class User", false, "before");

        // middleware
        $this->insertInArrayToFile(app_path('Http/Kernel.php'),
            "\n            \\Laravel\\Passport\\Http\\Middleware\\CreateFreshApiToken::class,",
            "'web' =>"
            );

        $this->insertInArrayToFile(app_path('Http/Kernel.php'),
            "
        'client.credentials' => \\Laravel\\Passport\\Http\\Middleware\\CheckClientCredentials::class,
        'scopes' => \\Laravel\\Passport\\Http\\Middleware\\CheckScopes::class,
        'scope' => \\Laravel\\Passport\\Http\\Middleware\\CheckForAnyScope::class,
                    ",
            "routeMiddleware ="
        );

        // Auth Provider
        $auth_provider_path = join_path($this->nsPath->getPath('provider'), 'AuthServiceProvider.php');
        $this->useClassAtFileHead($auth_provider_path, "Laravel\\Passport\\Passport");
        $this->insertInBlockToFile($auth_provider_path, "
        Passport::routes();
        Passport::tokensExpireIn(now()->addDays(7));
        Passport::refreshTokensExpireIn(now()->addDays(15));
        Passport::enableImplicitGrant();
        Passport::tokensCan([
	        'scope1' => 'Scope 1',
    		'scope2' => 'Scope 2',
	    ]);
        ", "function boot()");


        // route
        $this->template('oauth.php', join_path($this->nsPath->getPath('route'), 'api/v1/oauth.php'));

        // views
        $this->call("vendor:publish", [
            "--tag" => "passport-views",
        ]);

        // vue components
        $this->call("vendor:publish", [
            "--tag" => "passport-components",
        ]);


        $resource_path = $this->nsPath->getPath('resource', 'js');
        $files = `grep "const app = new Vue" "$resource_path" -R -l | grep "app.js"`;
        $files = empty($files) ? [] : explode(PHP_EOL, trim($files));

        $content = "
Vue.component(
    'passport-clients',
    require('@/js/components/passport/Clients.vue')
);

Vue.component(
    'passport-authorized-clients',
    require('@/js/components/passport/AuthorizedClients.vue')
);

Vue.component(
    'passport-personal-access-tokens',
    require('@/js/components/passport/PersonalAccessTokens.vue')
);
        ";

        foreach ($files as $file)
        {
            $this->insertToFile($file, $content, "const app = new Vue", false, "before");
        }

        if (empty($files))
        {
            $this->info("\nAdd following code to register Vue components");
            $this->warn($content);
            $this->info("Use following tags to show info");
            $this->warn("
        <passport-clients></passport-clients>
        <passport-authorized-clients></passport-authorized-clients>
        <passport-personal-access-tokens></passport-personal-access-tokens>
            ");
        }else{
            $this->warn("Please restart webpack building");
        }

        $client = DB::table('oauth_clients')->where([
            'personal_access_client' => 0,
            'password_client' => 1,
            'revoked' => 0,
        ])->first();

        $baseUrl = env('APP_URL');
        $this->info("Now you can test some api: ");

        $this->alert("Get Token");
        $this->info("curl -X POST \\
  $baseUrl/oauth/token \\
  -H 'Accept: application/json' \\
  -H 'Content-Type: application/json' \\
  -d '{
	\"grant_type\" : \"password\",
	\"client_id\" : {$client->id},
	\"client_secret\" : \"{$client->secret}\",
	\"username\": \"admin@admin.com\",
	\"password\": \"password\"
}'");
        $this->alert("Get resource");
        $this->info("curl -X GET \\
  $baseUrl/api/v1/oauth-user \\
  -H 'Accept: application/json' \\
  -H 'Authorization: Bearer <comment>YOUR_ACCESS_TOKEN</comment>'
  ");
    }

    private function socialite()
    {
        $this->composer('laravel/socialite', false);

        $socialites = ['facebook', 'twitter', 'linkedin', 'google', 'github', 'bitbucket'];

        // add config
        $services_config_file = join_path($this->nsPath->getPath('config'), 'services.php');

        $content = "";
        foreach ($socialites as $item)
        {
            $content .= "
    '$item' => [
        'client_id' => 'your-$item-app-id',
        'client_secret' => 'your-$item-app-secret',
        'redirect' => 'http://your-callback-url',
    ],
            ";
        }

        $this->insertInArrayToFile($services_config_file, $content, "return");

        // controller
        foreach ($socialites as $item)
        {
            $info = $this->nsPath->getClassInfoByType('controller', null, null, studly_case("{$item}Controller"), 'Auth');
            $this->template('AuthController.php', $info['output'], $info);
        }

        // routes
        $route_file = join_path($this->nsPath->getPath('route'), 'web.php');
        $content = "";
        foreach ($socialites as $item)
        {
            $content .= "
Route::get('login/$item', 'Auth\\{$item}Controller@redirectToProvider');
Route::get('login/$item/callback', 'Auth\\{$item}Controller@handleProviderCallback');
            ";
        }

        $this->appendToFile($route_file, $content);
    }

}
