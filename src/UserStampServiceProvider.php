<?php

namespace VelitSol\UserStamps;


use Illuminate\Support\ServiceProvider;

class  UserStampServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/userstamps.php',
            'userstamps'
        );

        $this->publishes([
            __DIR__ . '/config/userstamps.php' => config_path('userstamps.php'),
        ]);
    }

    public function register()
    {

    }

}