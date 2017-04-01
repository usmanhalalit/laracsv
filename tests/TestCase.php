<?php namespace Laracsv;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use PHPUnit\Framework\TestCase as PhpunitTestCase;

class TestCase extends PhpunitTestCase
{

    public function setUp()
    {
        $capsule = new Capsule;

        $capsule->addConnection(array(
            'driver'  => 'sqlite',
            'database'  => ':memory:',
        ));
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $this->createTables(Capsule::schema());
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
            $table->string('title', 40);
            $table->timestamps();
        });

        $schema->create('categories_products', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('category_id');
            $table->integer('product_id');
            $table->timestamps();
        });
    }

    private function seedData()
    {

    }
}
