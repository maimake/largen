<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class ComposerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     * Lifecycle:
     * <pre>
     * View::share() <== env/global variable
     * View::make()  <== new view instance
     *      View::creator() <== callback when creating
     * View::composer() <== callback before render
     * render view
     * </pre>
     *
     * @return void
     */
    public function boot()
    {
        // see: https://laravel.com/docs/master/views#passing-data-to-views
        View::share('title', config('app.name'));
        View::share('breadcrumbs', [
            ['label' => 'Home', 'url' => '/'],
        ]);


//        // see: https://laravel.com/docs/master/views#view-composers
//        // Using class based composers...
//        View::composer(
//            'profile', 'App\Http\ViewComposers\ProfileComposer'
//        );
//
//        // Using Closure based composers...
//        View::composer('*', function ($view) {
//            //
//        });
//
//        // work like composer.
//        // composer: will override controller data
//        // creator: will be override by controller data
//        View::creator('profile', 'App\Http\ViewCreators\ProfileCreator');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
