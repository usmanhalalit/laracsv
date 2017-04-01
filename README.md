# LaraCSV

A Laravel package to easily generate CSV files from Eloquent model.

## Basic usage

```php
$model = User::get();
$csvExporter = new \Laracsv\Csv\Export();
$csvExporter->build($model, ['email', 'name'])->download();
```

And a proper CSV file will be downloaded with `email` and `name` fields.

## Installation



### Preparation step:

Laracsv build method requires two parameter.
+ Your model.
+ Attributes of model.

```php
$csvExporter = new \Laracsv\Csv\Export();

// Variable $model is your typical Laravel Model
// Variable $modelAttributes is collection of model fields in an array.

$csvExporter->build($model, $modelAttributes);
```

## Advance Usage

### Model modification

```php
$csvExporter->beforeEach(function($model){
    // A callback function to modify models
});
```

### Model attributes modification

Renaming model attribute.

```php
$modelAttributes = ['id', 'attribute1', 'attribute2' => 'attribute 2'];
```
