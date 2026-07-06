<?php

namespace App\Providers;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Make website settings available to every view as $site.
        View::composer('*', function ($view) {
            $site = null;

            try {
                if (Schema::hasTable('site_settings')) {
                    $site = SiteSetting::current();
                }
            } catch (\Throwable $e) {
                $site = null;
            }

            $view->with('site', $site);
        });
    }
}
