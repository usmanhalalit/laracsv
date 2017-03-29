# LaraCSV

A wrapper for preparing and downloading of CSV file from your model, specifically made for Laravel project.

## Basic usage

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

###Download step:

To download
```php
$csvExporter->download();
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
