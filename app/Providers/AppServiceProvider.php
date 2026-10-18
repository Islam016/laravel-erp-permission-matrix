<?php

namespace App\Providers;

use App\Support\ModuleRegistry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModuleRegistry::class, fn ($app) => new ModuleRegistry($app['config']->get('modules')));
    }

    public function boot(): void
    {
        // Super roles pass every permission check, even for permissions
        // added to the registry after the role was last synced.
        Gate::before(function ($user) {
            $registry = app(ModuleRegistry::class);

            foreach ($user->getRoleNames() as $role) {
                if ($registry->isSuperRole($role)) {
                    return true;
                }
            }

            return null;
        });
    }
}
