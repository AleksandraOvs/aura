<?php

namespace Supplier_Importer\Import;

if (!defined('ABSPATH')) {
    exit;
}

class Import_Progress
{
    private $total = 0;
    private $processed = 0;
    private $created = 0;
    private $updated = 0;
    private $skipped = 0;
    private $errors = 0;

    public function __construct($total = 0)
    {
        $this->total = max(
            0,
            (int) $total
        );
    }

    public function increment_processed()
    {
        $this->processed++;
    }

    public function increment_created()
    {
        $this->created++;
    }

    public function increment_updated()
    {
        $this->updated++;
    }

    public function increment_skipped()
    {
        $this->skipped++;
    }

    public function increment_errors()
    {
        $this->errors++;
    }

    public function set_values($data)
    {
        if (!is_array($data)) {
            return;
        }

        $this->processed = max(
            0,
            (int) ($data['processed'] ?? 0)
        );

        $this->created = max(
            0,
            (int) ($data['created'] ?? 0)
        );

        $this->updated = max(
            0,
            (int) ($data['updated'] ?? 0)
        );

        $this->skipped = max(
            0,
            (int) ($data['skipped'] ?? 0)
        );

        $this->errors = max(
            0,
            (int) ($data['errors'] ?? 0)
        );
    }

    public function get_total()
    {
        return $this->total;
    }

    public function get_processed()
    {
        return $this->processed;
    }

    public function get_created()
    {
        return $this->created;
    }

    public function get_updated()
    {
        return $this->updated;
    }

    public function get_skipped()
    {
        return $this->skipped;
    }

    public function get_errors()
    {
        return $this->errors;
    }

    public function get_percent()
    {
        if ($this->total <= 0) {
            return 0;
        }

        return min(
            100,
            round(
                ($this->processed / $this->total) * 100,
                2
            )
        );
    }

    public function to_array()
    {
        return [
            'total'     => $this->total,
            'processed' => $this->processed,
            'created'   => $this->created,
            'updated'   => $this->updated,
            'skipped'   => $this->skipped,
            'errors'    => $this->errors,
            'percent'   => $this->get_percent(),
        ];
    }
}
