# LaraCSV

A Laravel package to easily generate CSV files from Eloquent model.

## Basic usage

```php
$users = User::get(); // all users
$csvExporter = new \Laracsv\Csv\Export();
$csvExporter->build($users, ['email', 'name'])->download();
```

And a proper CSV file will be downloaded with `email` and `name` fields.

## Installation

Just run

```
composer require usmanhalalit/laracsv:1.*
```
and you should be good to go.

## Detailed Usage

`$exporter->build($modelCollection, $fields)` takes two parameters. 
First one is the model (collection of models), and seconds one takes the field names
 you want to export.

```php
$csvExporter->build(User::get(), ['email', 'name', 'created_at']);
```

### Custom Header

Above code example will generate a CSV with headers email, name, created_at and corresponding rows after.

If you want to change the header with a custom label just pass it as array value:
```php
$csvExporter->build(User::get(), ['email', 'name' => 'Full Name', 'created_at' => 'Joined']);
```

Now `name` column will show the header `Full Name` but it will still take 
values from `name` field of the model. 

### Modify or Add Values

There is a hook which is triggered before processing a database row.
  For example if you want to change the date format you can do so.
```php
$csvExporter = new \Laracsv\Csv\Export();
$users = User::get();
$csvExporter->build($users, ['email', 'name' => 'Full Name', 'created_at' => 'Joined']);

$csvExporter->beforeEach(function($user) {
    $user->created_at = date('f', strtotime($user->created_at)); 
});
```

**Add fields and values**

You may also add fields that don't exists in a database table add values on the fly. 

```php
// The notes field doesn't exist so values for this field will be blank by default 
$csvExporter->build($users, ['email', 'notes']);

$csvExporter->beforeEach(function($user) {
    // Now notes field will have this value
    $user->notes = 'Add your notes'; 
});
```

### Model Relationships

You can also add fields in the CSV from related database tables, given the model
 has relationships defined.
 
This will get the product title and the related category's title (one to one). 
```php
$csvExporter->build($products, ['title', 'category.title']);
```

You may also tinker relation things as you wish with hooks:

```php
$fields = ['id', 'title', 'price', 'original_price' => 'Market Price', 'purchase_price', 'tags', 'status', 'category_ids',];
$csvExporter = new \Laracsv\Csv\Export();
$csvExporter->beforeEach(function ($product) {
    $product->category_ids = implode(', ', $product->categories->pluck('title')->toArray());
});
```