<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Spatie\Translatable\Facades\Translatable;

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
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->input('email').'|'.$request->ip());
        });

        // Se manca una traduzione, i contenuti ripiegano sull'italiano, l'unica lingua obbligatoria
        Translatable::fallback(fallbackLocale: 'it');

        // Permesso generale, non legato a un record: vedere il contenuto del cestino
        Gate::define('viewTrash', fn (User $user) => $user->role !== UserRole::Moderator);
    }
}
