<?php

use Illuminate\Support\Facades\Route;
use Larapress\FileShare\Controllers\FileUploadController;

// api routes with public access
Route::middleware(config('larapress.pages.middleware'))
    ->prefix(config('larapress.pages.prefix'))
    ->group(function () {
        FileUploadController::registerWebRoutes();
    });
