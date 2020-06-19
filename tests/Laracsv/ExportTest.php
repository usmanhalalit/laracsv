<?php

namespace Laracsv;

use Laracsv\Models\Category;
use Laracsv\Models\Product;
use League\Csv\Writer;
use stdClass;

class ExportTest extends TestCase
{
    public function testBasicCsv()
    {
        $products = Product::limit(10)->get();

        $fields = ['id', 'title', 'price', 'original_price',];

        $csvExporter = new Export();
        $csvExporter->build($products, $fields);
        $csv = $csvExporter->getReader();
        $lines = explode(PHP_EOL, trim($csv->getContent()));
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
        $csv = $csvExporter->getReader();
        $lines = explode(PHP_EOL, trim($csv->getContent()));
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

        $csv = $csvExporter->getReader();
        $lines = explode(PHP_EOL, trim($csv->getContent()));
        $firstLine = $lines[0];
        $thirdRow = explode(',', $lines[2]);
        $this->assertSame('id,Name,price,"Retail Price","Custom Field"', $firstLine);
        $this->assertEquals(30, $thirdRow[2]);
        $this->assertSame('"Test Value"', $thirdRow[4]);
        $this->assertCount(5, $lines);
    }

    public function testBeforeEachChunkCallback()
    {
        $export = new Export();

        // Verify that the categories are not loaded
        $export->beforeEach(function ($record) {
            $this->assertFalse($record->relationLoaded('categories'));
        });

        $export->buildFromBuilder(Product::select(), ['id']);

        // Eager load the categories per chunk
        $export->beforeEachChunk(function ($collection) {
            $collection->load('categories');
        });

        // Verify that the categories are eagerly loaded per chunk
        $export->beforeEach(function ($record) {
            $this->assertTrue($record->relationLoaded('categories'));
        });

        $export->buildFromBuilder(Product::select(), ['id']);
    }

    public function testBuilderChunkSize()
    {
        $export = new Export();

        $export->beforeEachChunk(function ($collection) {
            $this->assertSame(10, $collection->count());
        });

        $export->buildFromBuilder(Product::select(), ['formatted_property' => 'Property']);

        $export->beforeEachChunk(function ($collection) {
            $this->assertSame(5, $collection->count());
        });

        $export->buildFromBuilder(Product::select(), ['formatted_property' => 'Property'], ['chunk' => 5]);
    }

    public function testBuilderHeader()
    {
        $export = new Export();

        $export->buildFromBuilder(Product::select(), ['id' => 'ID Header', 'price']);

        $lines = explode(PHP_EOL, trim($export->getReader()->getContent()));

        $this->assertSame('"ID Header",price', $lines[0]);

        $export = new Export();

        $export->buildFromBuilder(Product::select(), ['id' => 'ID Header', 'price'], ['header' => false]);

        $lines = explode(PHP_EOL, trim($export->getReader()->getContent()));

        $this->assertNotSame('"ID Header",price', $lines[0]);
    }

    public function testBuilder()
    {
        $export = new Export();

        $export->beforeEach(function ($record) {
            $record->formatted_property = 'id_' . $record->id;
        });

        $export->buildFromBuilder(Product::select(), ['formatted_property'], ['header' => false]);

        $lines = explode(PHP_EOL, trim($export->getReader()->getContent()));

        foreach ($lines as $index => $line) {
            $this->assertSame('id_' . ($index + 1), $line);
        }
    }

    public function testUtf8()
    {
        foreach (range(11, 15) as $item) {
            $product = Product::create([
                'title' => 'رجا ابو سلامة',
                'price' => 70,
                'original_price' => 80,
            ]);

            $product->categories()->attach(Category::find(collect(range(1, 10))->random()));
        }

        $products = Product::where('title', 'رجا ابو سلامة')->get();
        $this->assertEquals('رجا ابو سلامة', $products->first()->title);

        $csvExporter = new Export();

        $csvExporter->build($products, ['title', 'price']);

        $csv = $csvExporter->getReader();
        $lines = explode(PHP_EOL, trim($csv->getContent()));

        $this->assertSame('"رجا ابو سلامة",70', $lines[2]);
    }

    public function testCustomLeagueCsvWriters()
    {
        $products = Product::limit(10)->get();

        $fields = ['id', 'title', 'price', 'original_price',];
        file_put_contents('test.csv', '');
        $csvExporter = new Export(Writer::createFromPath('test.csv', 'r+'));
        $csvExporter->build($products, $fields);
        $csv = $csvExporter->getReader();

        $lines = explode(PHP_EOL, trim($csv->getContent()));
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

        $csv = $csvExporter->getReader();

        $secondLine = explode(',', explode(PHP_EOL, trim($csv->getContent()))[1]);

        $this->assertCount(3, $secondLine); // There should be a parent id for each category
        $this->assertEquals(1, $secondLine[2]); // Parent ID is always seeded to #1
    }

    public function testIlluminateSupportCollection()
    {
        $faker = \Faker\Factory::create();

        $csvExporter = new Export();

        $data = [];
        for ($i = 1; $i < 5; $i++) {
            $data[] = [
                'id' => $i,
                'address' => $faker->streetAddress,
                'firstName' => $faker->firstName
            ];
        }
        $data = collect($data);
        $csvExporter->build($data, [
            'id',
            'firstName',
            'address'
        ]);

        $csv = $csvExporter->getWriter();
        $lines = explode(PHP_EOL, trim($csv));
        $this->assertCount(5, $lines);

        $fourthLine = explode(',', explode(PHP_EOL, trim($csv))[4]);

        $this->assertSame('4', $fourthLine[0]);
    }

    public function testExportPlainObjects()
    {
        $faker = \Faker\Factory::create();

        $csvExporter = new Export();

        $data = [];
        for ($i = 1; $i < 5; $i++) {
            $object = new stdClass();
            $object->id = $i;
            $object->address = $faker->streetAddress;
            $object->firstName = $faker->firstName;

            $data[] = $object;
        }

        $data = collect($data);

        $csvExporter->build($data, [
            'id',
            'firstName',
            'address'
        ]);

        $csv = trim($csvExporter->getWriter()->getContent());

        $lines = explode(PHP_EOL, $csv);
        $fourthLine = explode(',', $lines[4]);

        $this->assertCount(5, $lines);
        $this->assertSame('4', $fourthLine[0]);
    }

    public function testRead()
    {
        $products = Product::limit(10)->get();

        $fields = ['id', 'title', 'price', 'original_price',];

        $csvExporter = new Export();
        $csvExporter->build($products, $fields);
        $reader = $csvExporter->getReader();
        $this->assertCount(11, $reader);
        $this->assertEquals('title', $reader->fetchOne()[1]);
        $this->assertEquals(Product::first()->title, $reader->fetchOne(1)[1]);
    }

    public function testJson()
    {
        $products = Product::limit(10)->get();

        $fields = ['id', 'title', 'price', 'original_price',];

        $csvExporter = new Export();
        $csvExporter->build($products, $fields);
        $reader = $csvExporter->getReader();
        $this->assertEquals(Product::first()->title, $reader->jsonSerialize()[1][1]);
    }

    public function testWriter()
    {
        $products = Product::limit(10)->get();

        $fields = ['id', 'title', 'price', 'original_price',];

        $csvExporter = new Export();
        $csvExporter->build($products, $fields);
        $writer = $csvExporter->getWriter();
        $this->assertNotFalse(strstr($writer->getContent(), Product::first()->title));
    }

    public function testWithNoHeader()
    {
        $products = Product::limit(10)->get();

        $fields = ['id', 'title', 'price', 'original_price',];

        $csvExporter = new Export();
        $csvExporter->build($products, $fields, ['header' => false]);
        $csv = $csvExporter->getReader();
        $lines = explode(PHP_EOL, trim($csv->getContent()));
        $firstLine = $lines[0];
        $this->assertNotEquals("id,title,price,original_price", $firstLine);
        $this->assertCount(10, $lines);
        $this->assertCount(count($fields), explode(',', $lines[2]));
    }

    public function testWithCustomDelimeter()
    {
        $products = Product::limit(10)->get();

        $fields = ['id', 'title', 'price', 'original_price',];

        $csvExporter = new Export();
        $csvExporter->getWriter()->setDelimiter(';');
        $csvExporter->build($products, $fields);
        $csv = $csvExporter->getReader();
        $lines = explode(PHP_EOL, trim($csv->getContent()));
        $firstLine = $lines[0];
        $this->assertEquals("id;title;price;original_price", $firstLine);
        $this->assertCount(11, $lines);
        $this->assertCount(count($fields), explode(';', $lines[2]));
    }
}
