<?php

namespace VelitSol\UserStamps;


trait UserStampTrait
{


    public function getUserStamps()
    {
        return $this->userStamps;
    }

    protected function getRelationName($userstamp)
    {
        return lcfirst(join(array_map('ucfirst', explode('_', $userstamp))));
    }

    public function __call($method, $parameters)
    {

        if ($method == 'hydrate') {
            if (count($parameters) > 0 && !empty($this->userStamps)) {
                // get users ids
                $userIds = collect($parameters[0])->map(function ($parameter) {
                    foreach ($this->userStamps as $userStamp) {
                        if (!empty($parameter->{$userStamp})) return $parameter->{$userStamp};
                    }
                })->unique()->toArray();

                $users = \DB::table(app(config('userstamps.user_model', 'App\User::class'))
                    ->getTable())->whereIn('id', $userIds)->get();
                // associate users with relavent fields
                collect($parameters[0])->each(function ($parameter) use ($users) {
                    foreach ($this->userStamps as $userStamp) {
                        if (!empty($parameter->{$userStamp})) {
                            $parameter->{$this->getRelationName($userStamp)} = $users->where('id', $parameter->{$userStamp})->count() > 0 ?
                                (array)$users->where('id', $parameter->{$userStamp})->first() : [];
                        }
                    }
                });
            }

        }

        if (method_exists($this, '__callAfter')) {
            return $this->__callAfter($method, $parameters);
        }

        // Keep ownder's  ancestor functional
        if (method_exists(parent::class, '__call')) {
            return parent::__call($method, $parameters);
        }

        throw new BadMethodCallException('Method ' . static::class . '::' . $method . '() not found');
    }


}


