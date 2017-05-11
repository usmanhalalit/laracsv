<?php namespace Laracsv;

use Laracsv\Models\Category;
use Laracsv\Models\Product;
use League\Csv\Writer;

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
        $this->assertSame('id,Name,price,"Retail Price","Custom Field"', $firstLine);
    }

    public function testWithBeforeEachCallback()
    {
        $products = Product::limit(5)->get();

        $fields = ['id', 'title' => 'Name', 'price', 'original_price' => 'Retail Price', 'custom_field' => 'Custom Field'];

        $csvExporter = new Export();
        $csvExporter->beforeEach(function ($model) {
            if ($model->id == 2) {
                return false;
            }
            $model->custom_field = 'Test Value';
            $model->price = 30;
        });

        $csvExporter->build($products, $fields);

        $csv = $csvExporter->getCsv();
        $lines = explode(PHP_EOL, trim($csv));
        $firstLine = $lines[0];
        $thirdRow = explode(',', $lines[2]);
        $this->assertSame('id,Name,price,"Retail Price","Custom Field"', $firstLine);
        $this->assertEquals(30, $thirdRow[2]);
        $this->assertSame('"Test Value"', $thirdRow[4]);
        $this->assertCount(5, $lines);
    }

    public function testUtf8()
    {
        $faker = \Faker\Factory::create();

        foreach (range(11, 15) as $item) {
            $product = Product::create([
                'title' =>  'رجا ابو سلامة',
                'price' => 70,
                'original_price' => 80,
            ]);

            $product->categories()->attach(Category::find(collect(range(1, 10))->random()));
        }

        $products = Product::where('title',  'رجا ابو سلامة')->get();
        $this->assertEquals('رجا ابو سلامة', $products->first()->title);

        $csvExporter = new Export();

        $csvExporter->build($products, ['title', 'price']);

        $csv = $csvExporter->getCsv();
        $lines = explode(PHP_EOL, trim($csv));

        $this->assertSame('"رجا ابو سلامة",70', $lines[2]);
    }

    public function testCustomLeagueCsvWriters()
    {
        $products = Product::limit(10)->get();

        $fields = ['id', 'title', 'price', 'original_price',];
        file_put_contents('test.csv', '');
        $csvExporter = new Export(Writer::createFromPath('test.csv', 'r+'));
        $csvExporter->build($products, $fields);
        $csv = $csvExporter->getCsv();

        $lines = explode(PHP_EOL, trim($csv));
        $firstLine = $lines[0];
        $this->assertEquals("id,title,price,original_price", $firstLine);
        $this->assertCount(11, $lines);
        $this->assertCount(count($fields), explode(',', $lines[2]));
        unlink('test.csv');
    }

    public function testCaseSensitiveRelationNames()
    {
        $cntCategories = 5;
        $categories = Category::limit($cntCategories)->with('mainCategory')->get();

        $csvExporter = new Export();

        $csvExporter->build($categories, [
            'id',
            'title',
            'mainCategory.id' => 'Parent Category ID',
        ]);

        $csv = $csvExporter->getCsv();

        $secondLine = explode(',', explode(PHP_EOL, trim($csv))[1]);

        $this->assertCount(3, $secondLine); // There should be a parent id for each category
        $this->assertEquals(1, $secondLine[2]); // Parent ID is always seeded to #1
    }
}
