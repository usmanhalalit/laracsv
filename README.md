# LaraCSV

A wrapper for preparing and downloading of CSV file from your model, specifically made for Laravel project.

## Basic usage

###Preparation step:

Laracsv requires two parameter.
* Your model.
* Attributes of model.

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
