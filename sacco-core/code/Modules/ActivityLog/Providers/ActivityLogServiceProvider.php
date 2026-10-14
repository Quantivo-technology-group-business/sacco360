<?php

namespace Modules\ActivityLog\Providers;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;
use Modules\ActivityLog\Listeners\AuthEventListener;

class ActivityLogServiceProvider extends ServiceProvider
{
    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerEventListeners();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        //$this->registerFactories();
        $this->loadMigrationsFrom(module_path('ActivityLog', 'Database/Migrations'));
    }

    protected function registerEventListeners()
    {
        Event::listen(Login::class, [AuthEventListener::class, 'handleLogin']);
        Event::listen(Logout::class, [AuthEventListener::class, 'handleLogout']);
        Event::listen(Failed::class, [AuthEventListener::class, 'handleFailed']);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            module_path('ActivityLog', 'Config/config.php') => config_path('activitylog.php'),
        ], 'config');
        $this->mergeConfigFrom(
            module_path('ActivityLog', 'Config/config.php'), 'activitylog'
        );
        $this->mergeConfigFrom(
            module_path('ActivityLog', 'Config/menus.php'), 'activitylog.menus'
        );
        $this->mergeConfigFrom(
            module_path('ActivityLog', 'Config/permissions.php'), 'activitylog.permissions'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/activitylog');

        $sourcePath = module_path('ActivityLog', 'Resources/views');

        $this->publishes([
            $sourcePath => $viewPath
        ], 'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/activitylog';
        }, \Config::get('view.paths')), [$sourcePath]), 'activitylog');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/activitylog');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'activitylog');
        } else {
            $this->loadTranslationsFrom(module_path('ActivityLog', 'Resources/lang'), 'activitylog');
        }
    }

    /**
     * Register an additional directory of factories.
     *
     * @return void
     */
    public function registerFactories()
    {
        if (!app()->environment('production') && $this->app->runningInConsole()) {
            app(Factory::class)->load(module_path('ActivityLog', 'Database/factories'));
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
