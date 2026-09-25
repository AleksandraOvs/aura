<?php

namespace Supplier_Importer\Import;

if (!defined('ABSPATH')) {
    exit;
}

use Supplier_Importer\CSV\Csv_Reader;

class Import_Chunk
{
    private const CHUNK_SIZE = 2;

    private $reader;

    public function __construct(Csv_Reader $reader)
    {
        $this->reader = $reader;
    }

    public function read($offset = 0)
    {
        $offset = max(0, (int) $offset);

        $this->reader->open();

        $this->skip_rows($offset);

        $rows = [];

        for ($i = 0; $i < self::CHUNK_SIZE; $i++) {
            $row = $this->reader->read();

            if ($row === null) {
                break;
            }

            $rows[] = $row;
        }

        $this->reader->close();

        return $rows;
    }

    private function skip_rows($offset)
    {
        for ($i = 0; $i < $offset; $i++) {
            $row = $this->reader->read();

            if ($row === null) {
                break;
            }
        }
    }

    public function get_chunk_size()
    {
        return self::CHUNK_SIZE;
    }
}
