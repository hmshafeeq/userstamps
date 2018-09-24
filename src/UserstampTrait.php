<?php

namespace VelitSol\Userstamps;


trait UserstampTrait
{
    // Contains the userstamp fields which depend on a model event
    // Contains the userstamp fields which depends upon certain expressions
    // Contains the userstamp fields which depend on a some other field ( which should be dirty in this case )
    private $userstampsToInsert = [];

    // events to capture
    protected static $CREATING = 'creating';
    protected static $SAVING = 'saving';
    protected static $UPDATING = 'updating';
    protected static $DELETING = 'deleting';

    public static function bootUserStampTrait()
    {
        $self = new static();

        static::creating(function ($model) use ($self) {
            $self->setUserstampOnModel($model, self::$CREATING);
        });

        static::updating(function ($model) use ($self) {
            $self->setUserstampOnModel($model, self::$UPDATING);
        });

        static::saving(function ($model) use ($self) {
            if (!empty($model->id)) {
                $self->setUserstampOnModel($model, self::$SAVING);
            }
        });

        static::deleting(function ($model) use ($self) {
            $self->setUserstampOnModel($model, self::$DELETING);
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

        if (!empty($this->userstamps)) {
            foreach ($this->userstamps as $fieldName => $userstamp) {
                if (is_array($userstamp) && count($userstamp) > 0) {
                    if (count($userstamp) == 1 && !empty($userstamp['depends_on_event']) && $userstamp['depends_on_event'] == $eventName) {
                        $model->{$fieldName} = $loggedInUserId;
                    } else {
                        // check if no event specified along with field name
                        // or if event is specified then it should match the type event invoked
                        $isEventMatched = (empty($userstamp['depends_on_event']) || (!empty($userstamp['depends_on_event']) ? $userstamp['depends_on_event'] == $eventName : false));
                        if ($isEventMatched) {
                            $isFieldDirty = false;
                            if (!empty($userstamp['depends_on_field'])) {
                                $isFieldDirty = $model->isDirty($userstamp['depends_on_field']);
                            }

                            $isExpressionTrue = false;
                            if (!empty($userstamp['depends_on_expression'])) {

                                $expression = $userstamp['depends_on_expression'];
                                $pattern = '/\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/';
                                $matchCount = preg_match_all($pattern, $expression, $matches);
                                for ($i = 0; $i < $matchCount; $i++) {
                                    $expression = str_replace($matches[0][$i], '"' . (empty($model->{$matches[1][$i]}) ? null : $model->{$matches[1][$i]}) . '"', $expression);
                                }
                                $expression = "return " . $expression . ";";
                                $isExpressionTrue = eval($expression);
                            }
                            if (!empty($userstamp['depends_on_expression']) && !empty($userstamp['depends_on_field'])) {
                                if ($isFieldDirty && $isExpressionTrue) {
                                    $model->{$fieldName} = $loggedInUserId;
                                }
                            } elseif ($isFieldDirty || $isExpressionTrue) {
                                $model->{$fieldName} = $loggedInUserId;
                            }
                        }
                    }

                    // In case of userstamp for a model which is being soft deleted, we need to save it first.
                    if ($eventName == self::$DELETING && $this->isSoftDeleteEnabled() && !empty($model->{$fieldName})) {
                        $model->save();
                    }
                }
            }
        }
    }

    public function isSoftDeleteEnabled()
    {
        // ... check if 'this' model uses the soft deletes trait
        return in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($this)) && !$this->forceDeleting;
    }


    /***
     * Get userstamp field names from the userstamp array
     * @return mixed
     */
    public function getUserStampFields()
    {
        return collect($this->userstamps)->map(function ($v, $k) {
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
            if (count($parameters) > 0 && !empty($this->userstamps)) {
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
                            $parameter->{$this->getRelationName($userStamp)} = $users->where('id', $parameter->{$userStamp})->first();
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


