# Laravel Eloquent Userstamps

[![GitHub issues](https://img.shields.io/github/issues/hmshafeeq/userstamps.svg)](https://github.com/hmshafeeq/userstamps/issues)
[![GitHub forks](https://img.shields.io/github/forks/hmshafeeq/userstamps.svg)](https://github.com/hmshafeeq/userstamps/network)
[![GitHub stars](https://img.shields.io/github/stars/hmshafeeq/userstamps.svg)](https://github.com/hmshafeeq/userstamps/stargazers)

### A simple package to load userstamps without specifying any relationship to Users Model.

## Requirements


## Installation
Step 1: Install Through Composer 

````
composer require velitsol/userstamps
````
Step 2: Add the Service Provider
 
```php
// config/app.php

'providers' => [
    '...',
     VelitSol\UserStamps\UserStampServiceProvider::class
];
```

Step 3: Run the command below to publish the package config file config/userstamps.php
 
```php
php artisan vendor:publish
```

Step 4: Specify the user model class with correct namespace in config/userstamps.php
 
```php
<?php

return [
    'user_model' => \App\User::class
];

```



## Usage

Load the trait in your Model.

```php
use VelitSol\UserStamps\UserStampTrait;

class Example extends Model {

    use UserStampTrait;
    
    // Specify the fields for which you want to autoload the user model automatically
    protected $userStamps = [
       'created_by',
       'updated_by',
       'archived_by',
       'submitted_by',
       // append any other users fields you want to autoload with model
    ];
}
```

The following objects will be loaded automatically with model. 

```php
$model->createdBy;  
$model->updatedBy;  
$model->archivedBy; 
$model->submittedBy; 
```

## License

This open-source software is licensed under the [MIT license](https://opensource.org/licenses/MIT).