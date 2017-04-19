<?php namespace Laracsv;

use Laracsv\Models\Product;

class ExportTest extends TestCase
{
    public function testBasicCsv()
    {
        $products = Product::limit(10)->get();

        $fields = ['id', 'title', 'price', 'original_price',];

        $csvExporter = new Export();
        $csvExporter->build($products, $fields);
        $csv = (string) $csvExporter->getCsv();
        $lines = explode(PHP_EOL, trim($csv));
        $firstLine = $lines[0];
        $this->assertEquals("id,title,price,original_price", $firstLine);
        $this->assertCount(11, $lines);
        $this->assertCount(count($fields), explode(',', $lines[2]));
    }
}
