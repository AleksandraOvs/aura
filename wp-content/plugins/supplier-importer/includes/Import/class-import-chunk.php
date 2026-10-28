<?php

namespace Supplier_Importer\Import;

if (!defined('ABSPATH')) {
    exit;
}

class Import_Chunk
{
    private const CHUNK_SIZE = 1;

    private $reader;

    public function __construct(Row_Reader $reader)
    {
        $this->reader = $reader;
    }

    public function read($offset = 0)
    {
        $offset = max(0, (int) $offset);

        $this->reader->open();

        try {
            $this->skip_rows($offset);

            $rows = [];

            for ($i = 0; $i < self::CHUNK_SIZE; $i++) {
                $row = $this->reader->read();

                if ($row === null) {
                    break;
                }

                $rows[] = $row;
            }

            return $rows;
        } finally {
            $this->reader->close();
        }
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
