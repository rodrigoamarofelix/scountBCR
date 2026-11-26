<?php

namespace App\Providers;

use App\Models\GameMatch;
use App\Policies\GameMatchPolicy;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        App::setLocale(config('app.locale'));
        Carbon::setLocale(config('app.locale'));

        Gate::policy(GameMatch::class, GameMatchPolicy::class);
    }
}