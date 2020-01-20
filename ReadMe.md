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
     VelitSol\Userstamps\UserstampServiceProvider::class
];
```

## Usage

Load the trait in your model and see the magic.

#### Scenario 1 : Load Userstamps For A Model
You can configure this package to autoload the userstamp along with your model. This will be the case when userstamp is being set in controller or some where else manually.
```php
use VelitSol\Userstamps\UserstampTrait;

class Post extends Model {

    use UserstampTrait;

    protected $userstamps = [
       'created_by',
       'updated_by',
       'submitted_by',
       'deleted_by'
    ];
}
```
And then you can autoload these userstamps like,
```php
$posts = Post::withUserstamps()->get();
```
This will allow you to access the defined userstamps on your model as dynamic relationships
```php
$post->createdByUser;
$post->updatedByUser;
$post->submittedByUser;
$post->deletedByUser;
```

#### Scenario 2 : Insert & Load Userstamps For A Model
You can configure this package to handle the userstamp insertion behind the scenes. This will also load those userstamps when you will fetch the records with eloquent.
Auto insert will depend on,
1. Event ('creating', 'saving', 'updating', 'deleting')
2. Field
3. Expression
```php
use VelitSol\Userstamps\UserstampTrait;

class Post extends Model {

    use UserstampTrait;

    protected $userstamps = [
       // This userstamp should be set when 'creating' event is invoked.
       'created_by' => [
            'depends_on_event' => 'creating',
       ],
       // This userstamp should be set when 'creating' or 'updating' event is invoked.
       // This is an example, if a userstamp depends on multiple events
       'updated_by' => [
            'depends_on_event' => ['creating', 'updating'],
       ],
       'deleted_by' => [
             'depends_on_event' => 'deleting',
       ],

       // This userstamp should be set if "is_archived" is dirty (has some change in value)
       'archived_by' => [
            'depends_on_field' => 'is_archived'
       ],

       // This userstamp should be set if "updating" event is invoked on this model,
       // and "is_submitted" is dirty (has some change in value)
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
And then you can autoload these userstamps like,
```php
$posts = Post::withUserstamps()->get();
```
This will allow you to access the defined userstamps on your model as dynamic relationships
```php
$post->createdByUser;
$post->updatedByUser;
$post->archivedByUser;
$post->submittedByUser;
$post->suspendedByUser;
```

## License

This open-source software is licensed under the [MIT license](https://opensource.org/licenses/MIT).