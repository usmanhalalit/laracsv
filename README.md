# LaraCSV

A Laravel package to easily generate CSV files from Eloquent model.

[![Build Status](https://travis-ci.org/usmanhalalit/laracsv.svg?branch=master)](https://travis-ci.org/usmanhalalit/laracsv)

## Basic usage

```php
$users = User::get(); // All users
$csvExporter = new \Laracsv\Export();
$csvExporter->build($users, ['email', 'name'])->download();
```

And a proper CSV file will be downloaded with `email` and `name` fields.

## Installation

Just run this on your terminal:

```
composer require "usmanhalalit/laracsv:1.*@dev"
```
and you should be good to go.

## Full Documentation

 - [Build CSV](#build-csv)
 - [Output Options](#output-options)
    - [Download](#download) 
 - [Custom Headers](#custom-headers)
 - [Modify or Add Values](#modify-or-add-values)
    - [Add fields and values](#add-fields-and-values)
 - [Model Relationships](#model-relationships)


### Build CSV

`$exporter->build($modelCollection, $fields)` takes two parameters. 
First one is the model (collection of models), and seconds one takes the field names
 you want to export.

```php
$csvExporter->build(User::get(), ['email', 'name', 'created_at']);
```

### Output Options
#### Download

To get file downloaded to the browser:
```php
$csvExporter->download();
```

You can provide a filename if you wish:
```php
$csvExporter->download('active_users.csv');
```

If no filename is given a filename with date-time will be generated.

#### Advanced Outputs

LaraCSV uses [League CSV](http://csv.thephpleague.com/). You can do what League CSV 
is able to do. You can get the underlying League CSV instance by calling:

```php
$csv = $csvExporter->getCsv();
```

And then you can do several things like:
```php
$csv->toHTML(); // To output the CSV as an HTML table 
$csv->jsonSerialize()(); // To turn the CSV in to an array 
$csv = (string) $csv; // To get the CSV as string
echo $csv; // To print the CSV
```

For more information please check [League CSV documentation](http://csv.thephpleague.com/).


### Custom Headers

Above code example will generate a CSV with headers email, name, created_at and corresponding rows after.

If you want to change the header with a custom label just pass it as array value:
```php
$csvExporter->build(User::get(), ['email', 'name' => 'Full Name', 'created_at' => 'Joined']);
```

Now `name` column will show the header `Full Name` but it will still take 
values from `name` field of the model. 

### Modify or Add Values

There is a hook which is triggered before processing a database row.
  For example, if you want to change the date format you can do so.
```php
$csvExporter = new \Laracsv\Export();
$users = User::get();

// Register the hook before building
$csvExporter->beforeEach(function ($user) {
    $user->created_at = date('f', strtotime($user->created_at)); 
});

$csvExporter->build($users, ['email', 'name' => 'Full Name', 'created_at' => 'Joined']);
```

**Note:** If a `beforeEach` callback returns `false` then the entire will be 
excluded from the CSV. It can come handy to filter some rows.

#### Add fields and values

You may also add fields that don't exists in a database table add values on the fly:

```php
// The notes field doesn't exist so values for this field will be blank by default 
$csvExporter->beforeEach(function ($user) {
    // Now notes field will have this value
    $user->notes = 'Add your notes'; 
});

$csvExporter->build($users, ['email', 'notes']);
```

### Model Relationships

You can also add fields in the CSV from related database tables, given the model
 has relationships defined.
 
This will get the product title and the related category's title (one to one):
```php
$csvExporter->build($products, ['title', 'category.title']);
```

You may also tinker relation things as you wish with hooks:

```php
$products = Product::where('order_count', '>', 10)->orderBy('order_count', 'desc')->get();
$fields = ['id', 'title','original_price' => 'Market Price', 'category_ids',];
$csvExporter = new \Laracsv\Export();
$csvExporter->beforeEach(function ($product) {
    $product->category_ids = implode(', ', $product->categories->pluck('id')->toArray());
});
```

## Road Map

 - Import CSV
 
