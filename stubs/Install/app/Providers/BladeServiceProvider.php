<?php

namespace App\Providers;

use Blade;
use Illuminate\Support\ServiceProvider;

class BladeServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Blade::directive('showNull', function ($expression) {
            $nullDisplay = __('not set');
            return "<?php echo $expression ?? '<span class=\"not-set\">($nullDisplay)</span>'; ?>";
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
