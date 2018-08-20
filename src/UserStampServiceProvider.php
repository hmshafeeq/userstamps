<?php

namespace VelitSol\UserStamps;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\ServiceProvider;

class  UserStampServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Builder::macro('withUserStamps', function ($uTypes = []) {
            $model = $this->getModel();
            foreach ($uTypes as $uType) {
                $model->addExternalMethod($uType, function () use ($uType) {
                    return $this->belongsTo(\App\Models\User::class, $uType);
                });
                $this->with($uType);
            }
            return $this;
        });
    }

    public function register()
    {

    }

}