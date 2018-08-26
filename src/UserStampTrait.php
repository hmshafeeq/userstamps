<?php

namespace VelitSol\UserStamps;


trait UserStampTrait
{
    // Contains the userstamp fields which depend on a model event
    private $dependsOnEvent = [];
    // Contains the userstamp fields which depend on a some other field ( which should be dirty in this case )
    private $dependsOnField = [];
    // Contains the userstamp fields which depends upon certain expressions
    private $dependsOnExpression = [];

    public static function bootUserStampTrait()
    {
        $self = new static();

        if (!empty($self->userStamps)) {
            foreach ($self->userStamps as $k => $v) {
                if (is_array($v)) {
                    if (isset($v['depends_on_event'])) $self->dependsOnEvent[$k] = $v['depends_on_event'];
                    if (isset($v['depends_on_field'])) $self->dependsOnField[$k] = $v['depends_on_field'];
                    if (isset($v['depends_on_expression'])) $self->dependsOnExpression[$k] = $v['depends_on_expression'];
                }
            }
        }

        static::creating(function ($model) use ($self) {
            $self->setUserstampOnModel($model, 'creating');
        });

        static::updating(function ($model) use ($self) {
            $self->setUserstampOnModel($model, 'updating');
        });

        static::saving(function ($model) use ($self) {
            $self->setUserstampOnModel($model, 'saving');
        });

        static::deleting(function ($model) use ($self) {
            $self->setUserstampOnModel($model, 'deleting');
        });

    }

    /**
     * Set userstamp on the current model depending upon the
     * 1. Event
     * 2. Field
     * 3. Expression
     * @param $model
     * @param string $eventName
     */
    public function setUserstampOnModel(&$model, $eventName = '')
    {
        $loggedInUserId = auth()->id();

        if (!empty($eventName) && !empty($this->dependsOnEvent)) {
            // associate the logged in user id if userstamp depends upon the event
            foreach ($this->dependsOnEvent as $fieldName => $eName) {
                if ($eName == $eventName) {
                    $model->{$fieldName} = $loggedInUserId;
                }
            }
        } else if (!empty($this->dependsOnField)) {
            // associate the logged in user id if userstamp depend the change in some other field
            foreach ($this->dependsOnField as $fieldName => $fName) {
                if ($model->isDirty($fName)) {
                    $model->{$fieldName} = $loggedInUserId;
                }
            }
        } else if (!empty($this->dependsOnExpression)) {
            // associate the logged in user id if userstamp depends upon certain expression
            foreach ($this->dependsOnExpression as $fieldName => $expression) {
                $modelArray = $model->toArray();
                foreach (array_keys($modelArray) as $key) {
                    $expression = str_replace('$' . $key, !empty($modelArray[$key]) ? $modelArray[$key] : null, $expression);
                }
                $expression = "return " . $expression . ";";
                if (eval($expression)) {
                    $model->{$fieldName} = $loggedInUserId;
                }
            }
        }

    }

    /***
     * Get userstamp field names from the userstamp array
     * @return mixed
     */
    public function getUserStampFields()
    {
        return collect($this->userStamps)->map(function ($v, $k) {
            return is_array($v) ? $k : $v;
        })->values()->toArray();
    }

    /**
     * Create a relation name from the given userstamp field name
     * @param $userstamp
     * @return string
     */
    protected function getRelationName($userstamp)
    {
        return lcfirst(join(array_map('ucfirst', explode('_', $userstamp))));
    }

    /**
     * Override the default __call() method for query builder
     * It dynamically handle calls into the query instance.
     *
     * @param  string $method
     * @param  array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {

        if ($method == 'hydrate') {
            if (count($parameters) > 0 && !empty($this->userStamps)) {
                $userStampFields = $this->getUserStampFields();
                // get users ids
                $userIds = collect($parameters[0])->map(function ($parameter) use ($userStampFields) {
                    foreach ($userStampFields as $userStamp) {
                        if (!empty($parameter->{$userStamp})) return $parameter->{$userStamp};
                    }
                })->unique()->toArray();

                $users = \DB::table(app($this->getUserClass())
                    ->getTable())->whereIn('id', $userIds)->get();
                // associate users with relavent fields
                collect($parameters[0])->each(function ($parameter) use ($users, $userStampFields) {
                    foreach ($userStampFields as $userStamp) {
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

    /**
     * Get the class being used to provide a User.
     *
     * @return string
     */
    protected function getUserClass()
    {
        if (get_class(auth()) === 'Illuminate\Auth\Guard') {
            return auth()->getProvider()->getModel();
        }
        return auth()->guard()->getProvider()->getModel();
    }

}


