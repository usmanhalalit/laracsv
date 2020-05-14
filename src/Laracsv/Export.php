<?php

namespace Laracsv;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use League\Csv\Reader;
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
    protected $writer;

    /**
     * Export configuration.
     *
     * @var array
     */
    protected $config = [];

    /**
     * Export constructor.
     *
     * @param \League\Csv\Writer|null $writer
     */
    public function __construct(Writer $writer = null)
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
    public function build($collection, array $fields, array $config = []): self
    {
        $this->config = $config;
        $writer = $this->writer;
        $headers = [];

        foreach ($fields as $key => $field) {
            $headers[] = $field;

            if (!is_numeric($key)) {
                $fields[$key] = $key;
            }
        }

        $this->addHeader($writer, $headers);
        $this->addCsvRows($writer, $fields, $collection);

        return $this;
    }

    /**
     * Download the CSV file.
     *
     * @param string|null $filename
     */
    public function download($filename = null): void
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
    public function beforeEach(callable $callback): self
    {
        $this->beforeEachCallback = $callback;

        return $this;
    }

    /**
     * Get a CSV reader.
     *
     * @return Reader
     */
    public function getReader(): Reader
    {
        return Reader::createFromString($this->writer->getContent());
    }

    /**
     * Get the CSV writer.
     *
     * @return Writer
     */
    public function getWriter(): Writer
    {
        return $this->writer;
    }

    /**
     * Add rows to the CSV.
     *
     * @param Writer $writer
     * @param array $fields
     * @param \Illuminate\Support\Collection $collection
     * @throws \League\Csv\CannotInsertRecord
     */
    private function addCsvRows(Writer $writer, array $fields, Collection $collection): void
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

            if (!Arr::accessible($model)) {
                $model = collect($model);
            }

            $csvRow = [];
            foreach ($fields as $field) {
                $csvRow[] = Arr::get($model, $field);
            }

            $writer->insertOne($csvRow);
        }
    }

    /**
     * Adds a header row to the CSV.
     *
     * @param Writer $writer
     * @param array $headers
     */
    private function addHeader(Writer $writer, array $headers): void
    {
        if (Arr::get($this->config, 'header', true) !== false) {
            $writer->insertOne($headers);
        }
    }
}
