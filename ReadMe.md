# Laravel Eloquent Userstamps

[![GitHub issues](https://img.shields.io/github/issues/hmshafeeq/userstamps.svg)](https://github.com/hmshafeeq/userstamps/issues)
[![GitHub forks](https://img.shields.io/github/forks/hmshafeeq/userstamps.svg)](https://github.com/hmshafeeq/userstamps/network)
[![GitHub stars](https://img.shields.io/github/stars/hmshafeeq/userstamps.svg)](https://github.com/hmshafeeq/userstamps/stargazers)

### A simple package to load & insert userstamps for a model

## Requirements

* This package requires PHP 5.6+
* It works with Laravel 5.4 or later (and may work with earlier versions too).

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
 
## Usage

Load the trait in your model and see the magic. 

```php
use VelitSol\UserStamps\UserStampTrait;

class Example extends Model {

    use UserStampTrait;
     
    protected $userStamps = [
       
       /==============================================
       /  Auto load userstamp model for current model.
       /==============================================
       // If you don't want this pacakge to auto insert the userstamp, and you just want to autoload it along with your model
       // This will be the case when userstamp is being set in controller or some where else at certain action.
       'updated_by',
     
      /=========================================================
      /  Auto insert userstamp & auto load it for current model.
      /  Auto insert depends on,
      /  1. Event ('creating','saving','updating','deleting')
      /  2. Field 
      /  3. Expression
      /=========================================================
          
       // This userstamp should be updated when an event is invoked i.e 'creating','updating','deleting','saving'.
       'created_by' => [
            'depends_on_event' => 'creating', 
       ],
       
       // This userstamp should be set if "is_archived" is dirty (has some changes in value)
       'archived_by' => [
            'depends_on_field' => 'is_archived' 
       ],
       
       // This userstamp should be set if "updating" event is invoked on this model,
       // and "is_submitted" is dirty (has some changes in value)
       'submitted_by'=> [
            'depends_on_event' => 'updating', 
            'depends_on_field' => 'is_submitted' 
       ],
       
       // This userstamp should be set if "updating" event is invoked on this model,
       // and provided expression evaluates to true
       'suspended_by' => [
          'depends_on_event' => 'updating', 
          'depends_on_expression' => '$api_hits > 100' // $api_hits is a model field i.e $model->api_hits
       ],
       .............,
       ..............,
    ];
}
```

The following objects will be loaded automatically with the model in this case. 
```php
$model->createdBy;  
$model->updatedBy;  
$model->archivedBy; 
$model->submittedBy; 
$model->suspendedBy;
```


## License

This open-source software is licensed under the [MIT license](https://opensource.org/licenses/MIT).