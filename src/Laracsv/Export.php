<?php

namespace Laracsv;

use League\Csv\Writer;
use SplTempFileObject;

class Export
{
    /**
     * The applied callback.
     *
     * @var callable|null
     */
    protected $beforeEachCallback;

    /**
     * The CSV writer.
     *
     * @var \League\Csv\Writer
     */
    protected $csv;

    /**
     * Export constructor.
     *
     * @return void
     */
    public function __construct()
    {
        $this->csv = Writer::createFromFileObject(new SplTempFileObject);
    }

    /**
     * Build the writer.
     *
     * @param \Illuminate\Database\Eloquent\Collection $collection
     * @param array $fields
     * @return $this
     */
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

    /**
     * Download the CSV file.
     *
     * @param string|null $filename
     * @return void
     */
    public function download($filename = null)
    {
        $filename = $filename ?: date('Y-m-d_His') . '.csv';
        $this->csv->output($filename);
    }

    /**
     * Set the callback.
     *
     * @param callable $callback
     * @return $this
     */
    public function beforeEach(callable $callback)
    {
        $this->beforeEachCallback = $callback;
        return $this;
    }

    /**
     * Get the CSV writer.
     *
     * @return \League\Csv\Writer
     */
    public function getCsv()
    {
        return $this->csv;
    }

    /**
     * Ensure all fields of the model are visible.
     *
     * @param array $fields
     * @param \Illuminate\Database\Eloquent\Model $row
     * @return \Illuminate\Database\Eloquent\Model
     */
    private function makeAllFieldsVisible(array $fields, $row)
    {
        $row = $row->makeVisible($fields);
        return $row;
    }

    /**
     * Add rows to the CSV.
     *
     * @param \Illuminate\Database\Eloquent\Collection $collection
     * @param array $fields
     * @param \League\Csv\Writer $csv
     * @return void
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
