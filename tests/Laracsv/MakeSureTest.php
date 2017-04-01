<?php namespace Laracsv;

use Laracsv\Export;
use Laracsv\Models\Product;

class MakeSureTest extends TestCase
{
    public function testToMakeSure()
    {
        $this->assertTrue(true);
    }

    public function testBasicCsv()
    {
        $products = Product::limit(10)->get();

        $fields = ['id', 'title', 'price', 'original_price',];

        $csvExporter = new Export();
        $csvExporter->build($products, $fields);
        $csv = (string) $csvExporter->getCsv();
        $firstLine = explode(PHP_EOL, $csv)[0];
        $this->assertEquals("id,title,price,original_price", $firstLine);

    }
}
