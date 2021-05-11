<?php

namespace Larapress\FileShare\Providers;

use Illuminate\Support\ServiceProvider;
use Larapress\FileShare\Services\FileUpload\FileUploadService;
use Larapress\FileShare\Services\FileUpload\IFileUploadService;

class PackageServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(IFileUploadService::class, FileUploadService::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
        $this->loadMigrationsFrom(__DIR__.'/../../migrations');

        $this->publishes([
            __DIR__.'/../../config/fileshare.php' => config_path('larapress/fileshare.php'),
        ], ['config', 'larapress', 'larapress-fileshare']);
    }
}
