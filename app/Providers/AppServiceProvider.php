<?php

namespace App\Providers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging;



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

        Livewire::setScriptRoute(function ($handle) {
            return Route::get('/vendor/livewire/livewire.js', $handle)->middleware('web');
        });

        if (!App::environment('production')) {
            Livewire::setUpdateRoute(function ($handle) {
                return Route::post('/livewire/update', $handle)->middleware('web');
            });
        }

        Livewire::setUpdateRoute(function ($handle) {
            return Route::post('/new/smnapp/livewire/update', $handle)->middleware('web');
        });

        $factory = (new Factory)
            ->withServiceAccount(storage_path('app/firebase/firebase_credentials.json'))
            ->withProjectId(env('FIREBASE_PROJECT_ID', 'smnapp-20ba2'));

        $this->app->instance(Messaging::class, $factory->createMessaging());
    }
}
