<?php

return [
    'composer' => [
        'laravelcollective/html' => '5.7.*'
    ],
    'active_commands' => [
        'enable' => (env('APP_ENV') == 'local' || env('APP_DEBUG')),
        'blacklist' => [
            'prod',
            'production',
        ]
    ],
    'map' => [
        'app' => [
            'base' => base_path(),
            'namespace' => [
                'command' => 'Console\\Commands',
                'client' => 'Clients',
                'event' => 'Events',
                'exception' => 'Exceptions',
                'facade' => 'Facades',
                'controller' => 'Http\\Controllers',
                'middleware' => 'Http\\Middleware',
                'request' => 'Http\\Requests',
                'job' => 'Jobs',
                'listener' => 'Listeners',
                'mail' => 'Mail',
                'model' => 'Models',
                'search' => 'Models\\Search',
                'observer' => 'Observers',
                'policy' => 'Policies',
                'provider' => 'Providers',
                'repository' => 'Repositories',
                'service' => 'Services',
                'notification' => 'Notifications',
                'contract' => 'Contracts',
                'test' => 'Tests'
            ],
            'paths' => [
                'src' => 'app',
                'config' => 'config',
                'migration' => 'database/migrations',
                'factory' => 'database/factories',
                'seeder' => 'database/seeds',
                'public' => 'public',
                'resource' => 'resources',
                'lang' => 'resources/lang',
                'view' => 'resources/views',
                'asset' => 'resources/assets',
                'layout' => 'resources/views/layouts',
                'test' => 'tests',
                'webpack' => 'webpack',
                'route' => 'routes',
            ],
        ],

        'module' => [
            'base' => base_path('modules'),
            'namespace' => [
            ],
            'paths' => [
                'src' => '',
            ]
        ],

        'package' => [
            'base' => base_path('vendor'),
            'namespace' => [
            ],
            'paths' => [
                'src' => 'src',
            ]
        ],


//        'module/xxx' => [
//            'namespace' => [
//                'command' => 'Commands',
//            ],
//            'paths' => [
//                'src' => 'src_xxx',
//            ]
//        ],
//
//        'package/yyy/zzz' => [
//            'namespace' => [
//                'command' => 'Commands',
//            ],
//            'paths' => [
//                'src' => 'src_yyy',
//            ]
//        ]
    ],
    'stubs' => [
        base_path('stubs'),
    ]
];
