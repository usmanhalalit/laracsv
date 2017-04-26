<?php

namespace Laracsv;

use League\Csv\Writer as LeagueWriter;
use SplTempFileObject;

class Export
{
    protected $beforeEachCallback;
    /**
     * @var LeagueWriter
     */
    protected $csv;

    public function __construct()
    {
        $this->csv = LeagueWriter::createFromFileObject(new SplTempFileObject());
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

        // Add first line, the header
        $csv->insertOne($headers);

        $this->addCsvRows($collection, $fields, $csv);

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

    /**
     * @return LeagueWriter
     */
    public function getCsv()
    {
        return $this->csv;
    }

    private function makeAllFieldsVisible(array $fields, $row)
    {
        $row = $row->makeVisible($fields);
        return $row;
    }

    /**
     * @param $collection
     * @param array $fields
     * @param $csv
     */
    private function addCsvRows($collection, array $fields, $csv)
    {
        foreach ($collection as $model) {
            $beforeEachCallback = $this->beforeEachCallback;

            // Call hook
            if ($beforeEachCallback) {
                $return = $beforeEachCallback($model);
                if ($return === false) {
                    continue;
                }
            }

            $model = $this->makeAllFieldsVisible($fields, $model);
            $model->toArray();
            $csvRow = [];
            foreach ($fields as $field) {
                $csvRow[] = array_get($model, $field);
            }

            $csv->insertOne($csvRow);
        }
    }
}
