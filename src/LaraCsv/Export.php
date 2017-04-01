<?php

namespace Laracsv;

use League\Csv\Writer;
use SplTempFileObject;

class Export

{
    protected $beforeEachCallback;
    protected $csv;

    public function __construct()

    {
        $this->csv = Writer::createFromFileObject(new SplTempFileObject());
    }

    public function build($collection, array $fields)
    {
        $csv = $this->csv;
        $headers = [];

        foreach ($fields as $key => $field) {

            $headers[] = $field;

            if (! is_numeric($key)) {
                $fields[$key] = $key;
            }

        }

        $csv->insertOne($headers);

        foreach ($collection as $row) {

            $beforeEachCallback = $this->beforeEachCallback;

            if ($beforeEachCallback) {

                $return = $beforeEachCallback($row);

                if ($return === false) {
                    continue;
                }

            }


            $row = $this->makeAllFieldsVisible($fields, $row);
            $row->toArray();
            $csvRow = [];

            foreach ($fields as $field) {

                $csvRow[] = array_get($row, $field);

            }

            $csv->insertOne($csvRow);
        }

        return $this;
    }

    public function download($filename = null)

    {
        $filename = $filename ?: date('Y-m-d_His') . '.csv';
        $this->csv->output($filename);
    }

    public function beforeEach(callable $callback)

    {
        $this->beforeEachCallback = $callback;
        return $this;
    }

    public function getCsv()

    {
        return $this->csv;
    }

    private function makeAllFieldsVisible(array $fields, $row)

    {
        $row = $row->makeVisible($fields);
        return $row;
    }
}
