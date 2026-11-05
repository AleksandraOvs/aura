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

    /**
     * Количество обработанных записей.
     *
     * Для CSV — смещение по строкам.
     * Для YML — статистический счётчик.
     */
    private $offset = 0;

    private $total = 0;
    private $status = 'pending';

    /**
     * Параметры пакетного импорта.
     */
    private $run_id = '';
    private $format = 'csv';
    private $source = 'upload';
    private $phase = 'products';

    /**
     * Путь к промежуточному JSONL-файлу.
     */
    private $staging_file = '';

    /**
     * Байтовая позиция чтения JSONL.
     */
    private $cursor = 0;

    /**
     * Состояние синхронизации отсутствующих товаров.
     */
    private $sync_cursor = 0;
    private $sync_checked = 0;
    private $sync_unpublished = 0;
    private $sync_republished = 0;

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
        $this->offset = max(0, (int) $offset);
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

    /*
     * Run ID.
     */

    public function get_run_id()
    {
        return $this->run_id;
    }

    public function set_run_id($run_id)
    {
        $this->run_id = trim((string) $run_id);
    }

    /*
     * Формат импорта.
     */

    public function get_format()
    {
        return $this->format;
    }

    public function set_format($format)
    {
        $format = strtolower(trim((string) $format));

        $allowed_formats = [
            'csv',
            'yml',
            'xml',
        ];

        if (!in_array($format, $allowed_formats, true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Недопустимый формат импорта: %s',
                    $format
                )
            );
        }

        $this->format = $format;
    }

    /*
     * Источник импорта.
     */

    public function get_source()
    {
        return $this->source;
    }

    public function set_source($source)
    {
        $source = strtolower(trim((string) $source));

        $allowed_sources = [
            'upload',
            'manual',
            'cron',
        ];

        if (!in_array($source, $allowed_sources, true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Недопустимый источник импорта: %s',
                    $source
                )
            );
        }

        $this->source = $source;
    }

    /*
     * Текущая фаза импорта.
     */

    public function get_phase()
    {
        return $this->phase;
    }

    public function set_phase($phase)
    {
        $phase = strtolower(trim((string) $phase));

        $allowed_phases = [
            'prepare',
            'products',
            'sync',
            'completed',
        ];

        if (!in_array($phase, $allowed_phases, true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Недопустимая фаза импорта: %s',
                    $phase
                )
            );
        }

        $this->phase = $phase;
    }

    /*
     * Промежуточный JSONL-файл.
     */

    public function get_staging_file()
    {
        return $this->staging_file;
    }

    public function set_staging_file($staging_file)
    {
        $this->staging_file = trim((string) $staging_file);
    }

    /*
     * Байтовый курсор JSONL.
     */

    public function get_cursor()
    {
        return $this->cursor;
    }

    public function set_cursor($cursor)
    {
        $this->cursor = max(0, (int) $cursor);
    }

    /*
     * Курсор синхронизации товаров.
     */

    public function get_sync_cursor()
    {
        return $this->sync_cursor;
    }

    public function set_sync_cursor($sync_cursor)
    {
        $this->sync_cursor = max(0, (int) $sync_cursor);
    }

    public function get_sync_checked()
    {
        return $this->sync_checked;
    }

    public function set_sync_checked($count)
    {
        $this->sync_checked = max(0, (int) $count);
    }

    public function increment_sync_checked($count = 1)
    {
        $this->sync_checked += max(0, (int) $count);
    }

    public function get_sync_unpublished()
    {
        return $this->sync_unpublished;
    }

    public function set_sync_unpublished($count)
    {
        $this->sync_unpublished = max(0, (int) $count);
    }

    public function increment_sync_unpublished($count = 1)
    {
        $this->sync_unpublished += max(0, (int) $count);
    }

    public function get_sync_republished()
    {
        return $this->sync_republished;
    }

    public function set_sync_republished($count)
    {
        $this->sync_republished = max(0, (int) $count);
    }

    public function increment_sync_republished($count = 1)
    {
        $this->sync_republished += max(0, (int) $count);
    }

    /*
     * Прогресс импорта.
     */

    public function get_progress()
    {
        return $this->progress;
    }

    public function start()
    {
        if ($this->is_finished()) {
            throw new InvalidArgumentException(
                'Нельзя запустить завершённую сессию импорта.'
            );
        }

        $this->status = 'running';

        if ($this->started_at === null) {
            $this->started_at = current_time('mysql');
        }
    }

    public function complete()
    {
        $this->status = 'completed';
        $this->phase = 'completed';
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

    /*
     * Сериализация сессии.
     */

    public function to_array()
    {
        return [
            'import_id'         => $this->import_id,
            'supplier'          => $this->supplier,
            'file'              => $this->file,
            'offset'            => $this->offset,
            'total'             => $this->total,
            'status'            => $this->status,

            'run_id'            => $this->run_id,
            'format'            => $this->format,
            'source'            => $this->source,
            'phase'             => $this->phase,

            'staging_file'      => $this->staging_file,
            'cursor'            => $this->cursor,

            'sync_cursor'       => $this->sync_cursor,
            'sync_checked'      => $this->sync_checked,
            'sync_unpublished'  => $this->sync_unpublished,
            'sync_republished'  => $this->sync_republished,

            'progress'          => $this->progress->to_array(),

            'started_at'        => $this->started_at,
            'finished_at'       => $this->finished_at,
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
            $data['import_id'] ?? $data['id'] ?? 0
        );

        $session->set_offset(
            $data['offset'] ?? 0
        );

        $session->set_status(
            $data['status'] ?? 'pending'
        );

        $session->set_run_id(
            $data['run_id'] ?? ''
        );

        $session->set_format(
            $data['format'] ?? 'csv'
        );

        $session->set_source(
            $data['source'] ?? 'upload'
        );

        $session->set_phase(
            $data['phase'] ?? 'products'
        );

        $session->set_staging_file(
            $data['staging_file'] ?? ''
        );

        $session->set_cursor(
            $data['cursor'] ?? 0
        );

        $session->set_sync_cursor(
            $data['sync_cursor'] ?? 0
        );

        $session->set_sync_checked(
            $data['sync_checked'] ?? 0
        );

        $session->set_sync_unpublished(
            $data['sync_unpublished'] ?? 0
        );

        $session->set_sync_republished(
            $data['sync_republished'] ?? 0
        );

        $session->set_started_at(
            $data['started_at'] ?? null
        );

        $session->set_finished_at(
            $data['finished_at'] ?? null
        );

        $progress = $data['progress'] ?? $data;

        $session->restore_progress([
            'processed' => $progress['processed'] ?? 0,
            'created'   => $progress['created'] ?? 0,
            'updated'   => $progress['updated'] ?? 0,
            'skipped'   => $progress['skipped'] ?? 0,
            'errors'    => $progress['errors'] ?? 0,
        ]);

        return $session;
    }

    public function restore_progress($data)
    {
        $this->progress->set_values($data);
    }
}
