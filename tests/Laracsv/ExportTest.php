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
        $csv = $csvExporter->getCsv();
        $lines = explode(PHP_EOL, trim($csv));
        $firstLine = $lines[0];
        $this->assertEquals("id,title,price,original_price", $firstLine);
        $this->assertCount(11, $lines);
        $this->assertCount(count($fields), explode(',', $lines[2]));
    }

    public function testWithCustomHeaders()
    {
        $products = Product::limit(5)->get();

        $fields = ['id', 'title' => 'Name', 'price', 'original_price' => 'Retail Price', 'custom_field' => 'Custom Field'];

        $csvExporter = new Export();
        $csvExporter->build($products, $fields);
        $csv = $csvExporter->getCsv();
        $lines = explode(PHP_EOL, trim($csv));
        $firstLine = $lines[0];
        $this->assertEquals('id,Name,price,"Retail Price","Custom Field"', $firstLine);
    }

    public function testWithBeforeEachCallback()
    {
        $products = Product::limit(5)->get();

        $fields = ['id', 'title' => 'Name', 'price', 'original_price' => 'Retail Price', 'custom_field' => 'Custom Field'];

        $csvExporter = new Export();
        $csvExporter->beforeEach(function ($model) {
            $model->custom_field = 'Test Value';
            $model->price = 30;
        });

        $csvExporter->build($products, $fields);

        $csv = $csvExporter->getCsv();
        $lines = explode(PHP_EOL, trim($csv));
        $firstLine = $lines[0];
        $thirdRow = explode(',', $lines[2]);
        $this->assertEquals('id,Name,price,"Retail Price","Custom Field"', $firstLine);
        $this->assertEquals(30, $thirdRow[2]);
        $this->assertEquals('"Test Value"', $thirdRow[4]);
    }
}
