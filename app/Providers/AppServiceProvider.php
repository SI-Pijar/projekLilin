<?php

namespace App\Providers;

use App\Models\Analisis;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));

        foreach (['analisis', 'tantangan', 'review', 'pembayaran', 'user'] as $parameter) {
            Route::pattern($parameter, '[0-9]{1,15}');
        }

        Gate::define('milik-analisis', fn (User $user, Analisis $analisis) => $analisis->user_id === $user->id);

        if ($this->app->environment('production') && config('app.url')) {
            URL::forceRootUrl(config('app.url'));
        }

        RateLimiter::for('masuk', fn (Request $request) => [
            Limit::perMinute(5)->by('masuk|' . mb_strtolower((string) $request->input('email')) . '|' . $request->ip()),
            Limit::perMinute(60)->by('masuk-ip|' . $request->ip()),
        ]);
        RateLimiter::for('daftar', fn (Request $request) => Limit::perMinute(20)->by('daftar|' . $request->ip()));

        RateLimiter::for('analisis', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip()));
    }
}
