<?php

namespace Laracsv;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Laracsv\Models\Category;
use Laracsv\Models\Product;
use PHPUnit\Framework\TestCase as PhpunitTestCase;

class TestCase extends PhpunitTestCase
{
    public function setUp()
    {
        $capsule = new Capsule;

        $capsule->addConnection(array(
            'driver' => 'sqlite',
            'database' => ':memory:',
        ));
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $this->createTables(Capsule::schema());
        $this->seedData();
    }

    public function tearDown()
    {
    }

    private function createTables($schema)
    {
        $schema->create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title', 100);
            $table->decimal('price', 10, 2);
            $table->decimal('original_price', 10, 2)->nullable();
            $table->timestamps();
        });

        $schema->create('categories', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('parent_id');
            $table->string('title', 40);
            $table->timestamps();
        });

        $schema->create('category_product', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('category_id');
            $table->integer('product_id');
        });
    }

    private function seedData()
    {
        $faker = \Faker\Factory::create();
        foreach (range(1, 10) as $id => $item) {
            Category::create([
                'id' => $id,
                'parent_id' => 1,
                'title' => $faker->name,
            ]);
        }

        foreach (range(1, 10) as $item) {
            $product = Product::create([
                'title' => $faker->name,
                'price' => collect(range(4, 100))->random(),
                'original_price' => collect(range(5, 120))->random(),
            ]);

            $product->categories()->attach(Category::find(collect(range(1, 10))->random()));
        }
    }
}
