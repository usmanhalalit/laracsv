<?php

namespace Laracsv;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use League\Csv\Reader;
use League\Csv\Writer;
use SplTempFileObject;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use League\Csv\AbstractCsv as LeagueCsvWriter;

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
    protected $writer;

    /**
     * Configuration
     *
     * @var array
     */
    protected $config = [];

    /**
     * @var bool
     */
    protected $isEloquentCollection = false;

    /**
     * Export constructor.
     *
     * @param \League\Csv\AbstractCsv|null $writer
     * @return void
     */
    public function __construct(LeagueCsvWriter $writer = null)
    {
        $this->writer = $writer ?: Writer::createFromFileObject(new SplTempFileObject);
    }

    /**
     * Build the writer.
     *
     * @param \Illuminate\Support\Collection $collection
     * @param array $fields
     * @param array $config
     * @return $this
     * @throws \League\Csv\CannotInsertRecord
     */
    public function build($collection, array $fields, $config = [])
    {
        $this->config = $config;
        $csv = $this->writer;
        $headers = [];

        foreach ($fields as $key => $field) {
            $headers[] = $field;

            if (!is_numeric($key)) {
                $fields[$key] = $key;
            }
        }

        if (is_a($collection, EloquentCollection::class)) {
            $this->isEloquentCollection = true;
        }

        $this->addHeader($csv, $headers);
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
        $this->writer->output($filename);
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
     * Get a CSV reader.
     *
     * @return Reader
     */
    public function getReader()
    {
        return Reader::createFromString($this->writer->getContent());
    }

    /**
     * Get the CSV writer.
     *
     * @return Writer
     */
    public function getWriter()
    {
        return $this->writer;
    }

    /**
     * Add rows to the CSV.
     *
     * @param \Illuminate\Support\Collection $collection
     * @param array $fields
     * @param \League\Csv\Writer $csv
     * @return void
     * @throws \League\Csv\CannotInsertRecord
     */
    private function addCsvRows(Collection $collection, array $fields, Writer $csv)
    {
        if ($this->isEloquentCollection) {
            $collection->makeVisible($fields);
        }

        foreach ($collection as $model) {
            $beforeEachCallback = $this->beforeEachCallback;
            // Call hook
            if ($beforeEachCallback) {
                $return = $beforeEachCallback($model);
                if ($return === false) {
                    continue;
                }
            }

            if (! $this->isEloquentCollection) {
                $model = collect($model);
            }
            $model->toArray();

            $csvRow = [];
            foreach ($fields as $field) {
                $csvRow[] = Arr::get($model, $field);
            }

            $csv->insertOne($csvRow);
        }
    }

    /**
     * @param $csv
     * @param array $headers
     */
    private function addHeader($csv, array $headers): void
    {
        if (!isset($this->config['header']) || $this->config['header'] !== false) {
            $csv->insertOne($headers);
        }
    }
}
