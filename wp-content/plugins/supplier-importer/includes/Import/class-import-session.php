<?php

namespace Supplier_Importer\Import;

if (!defined('ABSPATH')) {
    exit;
}

use InvalidArgumentException;

class Import_Session
{
    private $import_id = 0;
    private $supplier = '';
    private $file = '';
    private $offset = 0;
    private $total = 0;
    private $status = 'pending';

    private $progress;

    private $started_at = null;
    private $finished_at = null;

    public function __construct(
        $supplier,
        $file,
        $total = 0,
        $import_id = 0
    ) {
        $supplier = trim((string) $supplier);
        $file = trim((string) $file);

        if ($supplier === '') {
            throw new InvalidArgumentException(
                'Import_Session: не указан поставщик.'
            );
        }

        if ($file === '') {
            throw new InvalidArgumentException(
                'Import_Session: не указан файл.'
            );
        }

        $this->import_id = absint($import_id);
        $this->supplier = $supplier;
        $this->file = $file;
        $this->total = max(0, (int) $total);

        $this->progress = new Import_Progress(
            $this->total
        );
    }

    public function get_import_id()
    {
        return $this->import_id;
    }

    public function set_import_id($import_id)
    {
        $this->import_id = absint($import_id);
    }

    public function get_supplier()
    {
        return $this->supplier;
    }

    public function get_file()
    {
        return $this->file;
    }

    public function get_offset()
    {
        return $this->offset;
    }

    public function set_offset($offset)
    {
        $this->offset = max(
            0,
            (int) $offset
        );
    }

    public function get_total()
    {
        return $this->total;
    }

    public function get_status()
    {
        return $this->status;
    }

    public function set_status($status)
    {
        $allowed_statuses = [
            'pending',
            'running',
            'completed',
            'failed',
            'cancelled',
        ];

        if (!in_array($status, $allowed_statuses, true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Недопустимый статус импорта: %s',
                    $status
                )
            );
        }

        $this->status = $status;
    }

    public function get_progress()
    {
        return $this->progress;
    }

    public function start()
    {
        $this->status = 'running';

        if ($this->started_at === null) {
            $this->started_at = current_time('mysql');
        }
    }

    public function complete()
    {
        $this->status = 'completed';
        $this->finished_at = current_time('mysql');
    }

    public function fail()
    {
        $this->status = 'failed';
        $this->finished_at = current_time('mysql');
    }

    public function cancel()
    {
        $this->status = 'cancelled';
        $this->finished_at = current_time('mysql');
    }

    public function get_started_at()
    {
        return $this->started_at;
    }

    public function set_started_at($started_at)
    {
        $this->started_at = $started_at
            ? (string) $started_at
            : null;
    }

    public function get_finished_at()
    {
        return $this->finished_at;
    }

    public function set_finished_at($finished_at)
    {
        $this->finished_at = $finished_at
            ? (string) $finished_at
            : null;
    }

    public function is_finished()
    {
        return in_array(
            $this->status,
            [
                'completed',
                'failed',
                'cancelled',
            ],
            true
        );
    }

    public function to_array()
    {
        return [
            'import_id'  => $this->import_id,
            'supplier'   => $this->supplier,
            'file'       => $this->file,
            'offset'     => $this->offset,
            'total'      => $this->total,
            'status'     => $this->status,
            'progress'   => $this->progress->to_array(),
            'started_at' => $this->started_at,
            'finished_at' => $this->finished_at,
        ];
    }

    public static function from_array($data)
    {
        if (!is_array($data)) {
            throw new InvalidArgumentException(
                'Import_Session::from_array ожидает массив.'
            );
        }

        $session = new self(
            $data['supplier'] ?? '',
            $data['file'] ?? '',
            $data['total'] ?? 0,
            $data['id'] ?? 0
        );

        $session->set_offset(
            $data['offset'] ?? 0
        );

        $session->set_status(
            $data['status'] ?? 'pending'
        );

        $session->set_started_at(
            $data['started_at'] ?? null
        );

        $session->set_finished_at(
            $data['finished_at'] ?? null
        );

        $session->restore_progress([
            'processed' => $data['processed'] ?? 0,
            'created'   => $data['created'] ?? 0,
            'updated'   => $data['updated'] ?? 0,
            'skipped'   => $data['skipped'] ?? 0,
            'errors'    => $data['errors'] ?? 0,
        ]);

        return $session;
    }

    public function restore_progress($data)
    {
        $this->progress->set_values($data);
    }
}
