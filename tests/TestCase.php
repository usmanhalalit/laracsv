<?php namespace Laracsv;

use Illuminate\Database\Capsule\Manager as Capsule;
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

        Capsule::statement(
            "CREATE TABLE sample(
               t_key             TEXT     NOT NULL,
               t_value           TEXT    NOT NULL
            );"
        );

    }

    public function tearDown()
    {

    }
}
