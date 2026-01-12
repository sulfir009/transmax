<?php

namespace App\Providers;

use App\Service\User;
use Illuminate\Support\ServiceProvider;

class UserServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('user', function () {
            return new User();
        });
    }

    public function boot()
    {

    }
}
