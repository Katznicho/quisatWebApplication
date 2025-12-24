<?php

namespace App\Providers;


use App\Models\Business;
use App\Models\Transaction;


// Import models and observers
use App\Models\User;
use App\Observers\ModelActivityObserver;
use Illuminate\Support\Facades\Auth;
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
        View::composer('*', function ($view) {
            if (Auth::check()) {
                // Refresh the business relationship to ensure we have the latest data
                $user = Auth::user();
                $user->load(['business.businessCategory']);
                $view->with('business', $user->business);
            } else {
                $view->with('business', null);
            }
        });

         // Register observers
         User::observe(ModelActivityObserver::class);
         Business::observe(ModelActivityObserver::class);
         Transaction::observe(ModelActivityObserver::class);

         
    }
}
